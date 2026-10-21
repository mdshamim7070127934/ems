<?php

namespace App\Http\Controllers\Master;

use App\Enums\Master\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UpdateEditorSettingRequest;
use App\Http\Resources\Master\EditorSettingResource;
use App\Models\Master\EditorSetting;
use App\Services\Master\EditorSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditorSettingController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['code', 'name', 'sort_order', 'status', 'is_default', 'created_at'];

    /**
     * @var list<int>
     */
    private const PAGE_SIZES = [10, 25, 50, 100];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $editorSettings = EditorSetting::query()
            ->search($filters['search'])
            ->orderBy($filters['sortBy'], $filters['sortOrder'])
            ->orderBy('id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/editor-settings/index', [
            'editorSettings' => [
                'data' => EditorSettingResource::collection($editorSettings->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $editorSettings->currentPage(),
                    'last_page' => $editorSettings->lastPage(),
                    'per_page' => $editorSettings->perPage(),
                    'total' => $editorSettings->total(),
                    'from' => $editorSettings->firstItem(),
                    'to' => $editorSettings->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateEditorSettingRequest $request,
        EditorSetting $editorSetting,
        EditorSettingService $editors,
    ): RedirectResponse {
        $validated = $request->validated();

        $updated = $editors->update($editorSetting, [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) $validated['sort_order'],
            'status' => Status::from($validated['status']),
        ]);

        Inertia::flash('toast', [
            'type' => $updated ? 'success' : 'error',
            'message' => $updated
                ? __('Editor setting updated.')
                : __('The default editor cannot be inactive. Set another editor as default first.'),
        ]);

        return back();
    }

    /**
     * Mark the editor as the system-wide default for compose and reply.
     */
    public function makeDefault(EditorSetting $editorSetting, EditorSettingService $editors): RedirectResponse
    {
        $updated = $editors->makeDefault($editorSetting);

        Inertia::flash('toast', [
            'type' => $updated ? 'success' : 'error',
            'message' => $updated
                ? __('Default editor updated.')
                : __('Only an active editor can be set as the default.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EditorSetting $editorSetting, EditorSettingService $editors): RedirectResponse
    {
        $deleted = $editors->delete($editorSetting);

        Inertia::flash('toast', [
            'type' => $deleted ? 'success' : 'error',
            'message' => $deleted
                ? __('Editor setting deleted.')
                : __('The default editor cannot be deleted. Set another editor as default first.'),
        ]);

        return back();
    }

    /**
     * @return array{search: string, sortBy: string, sortOrder: 'asc'|'desc', page: int, pageSize: int}
     */
    private function filters(Request $request): array
    {
        $search = trim($request->string('search')->toString());
        $sortBy = $request->string('sortBy')->toString();
        $sortOrder = strtolower($request->string('sortOrder')->toString()) === 'asc' ? 'asc' : 'desc';
        $pageSize = $request->integer('pageSize', 10);
        $page = $request->integer('page', 1);

        return [
            'search' => $search,
            'sortBy' => in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'sort_order',
            'sortOrder' => $sortOrder,
            'page' => $page > 0 ? $page : 1,
            'pageSize' => in_array($pageSize, self::PAGE_SIZES, true) ? $pageSize : 10,
        ];
    }
}

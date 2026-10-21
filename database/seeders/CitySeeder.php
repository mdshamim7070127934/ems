<?php

namespace Database\Seeders;

use App\Enums\Master\Status;
use App\Models\Master\Country;
use App\Models\Master\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed cities mapped to existing countries and states.
     */
    public function run(): void
    {
        $this->call(StateSeeder::class);

        DB::connection()->disableQueryLog();

        $countryIds = Country::query()->pluck('id', 'sortname');
        $stateIds = State::query()
            ->get(['id', 'country_id', 'name'])
            ->mapWithKeys(fn (State $state): array => [
                $state->country_id.'|'.$state->name => $state->id,
            ]);
        $now = now()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($countryIds, $stateIds, $now): void {
            $rows = [];

            foreach (WorldLocationDataset::all() as $iso2 => $states) {
                if (! $countryIds->has($iso2)) {
                    continue;
                }

                $countryId = (int) $countryIds->get($iso2);

                foreach ($states as $stateName => $cities) {
                    $stateId = $stateIds->get($countryId.'|'.$stateName);

                    if ($stateId === null) {
                        continue;
                    }

                    foreach ($cities as $cityName) {
                        $rows[] = [
                            'country_id' => $countryId,
                            'state_id' => (int) $stateId,
                            'name' => $cityName,
                            'status' => Status::Active->value,
                            'created_on' => $now,
                            'updated_on' => $now,
                        ];

                        if (count($rows) === 200) {
                            DB::table('mst_city')->insertOrIgnore($rows);
                            $rows = [];
                        }
                    }
                }
            }

            if ($rows !== []) {
                DB::table('mst_city')->insertOrIgnore($rows);
            }
        });
    }
}

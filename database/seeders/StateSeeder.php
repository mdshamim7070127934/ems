<?php

namespace Database\Seeders;

use App\Enums\Master\Status;
use App\Models\Master\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed states mapped to existing countries by ISO sortname.
     */
    public function run(): void
    {
        $this->call(CountrySeeder::class);

        DB::connection()->disableQueryLog();

        $countryIds = Country::query()->pluck('id', 'sortname');
        $now = now()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($countryIds, $now): void {
            $rows = [];

            foreach (WorldLocationDataset::all() as $iso2 => $states) {
                if (! $countryIds->has($iso2)) {
                    continue;
                }

                $countryId = (int) $countryIds->get($iso2);

                foreach (array_keys($states) as $stateName) {
                    $rows[] = [
                        'country_id' => $countryId,
                        'name' => $stateName,
                        'status' => Status::Active->value,
                        'created_on' => $now,
                        'updated_on' => $now,
                    ];

                    if (count($rows) === 200) {
                        DB::table('mst_state')->insertOrIgnore($rows);
                        $rows = [];
                    }
                }
            }

            if ($rows !== []) {
                DB::table('mst_state')->insertOrIgnore($rows);
            }
        });
    }
}

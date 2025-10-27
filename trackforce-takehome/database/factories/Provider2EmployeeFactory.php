<?php

namespace Database\Factories;

use Faker\Generator as Faker;

class Provider2EmployeeFactory
{
    /**
     * Generate a single Provider 2 employee payload matching schemas/provider2-schema.json
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function make(array $overrides = []): array
    {
        $faker = app(Faker::class);

        $givenName = $faker->firstName();
        $familyName = $faker->lastName();

        $payload = [
            'employee_number' => 'P2_' . $faker->unique()->numerify('###'),
            'personal_info' => [
                'given_name' => $givenName,
                'family_name' => $familyName,
                'email' => strtolower($givenName . '.' . $familyName) . '@provider2.example',
                'mobile' => $faker->e164PhoneNumber(),
            ],
            'work_info' => [
                'role' => $faker->jobTitle(),
                'division' => $faker->randomElement(['Security', 'Security Operations', 'Night Shift Security', 'Day Shift Security']),
                'start_date' => $faker->date('Y-m-d', 'now'),
                'current_status' => $faker->randomElement(['employed', 'terminated', 'on_leave']),
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }

    /**
     * Generate many Provider 2 employee payloads.
     *
     * @param int $count
     * @param array<string, mixed>|callable(int): array<string, mixed> $state
     * @return array<int, array<string, mixed>>
     */
    public static function makeMany(int $count, array|callable $state = []): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $overrides = is_callable($state) ? $state($i) : $state;
            $items[] = self::make($overrides);
        }

        return $items;
    }
}



<?php

namespace Database\Factories;


class Provider1EmployeeFactory
{
    /**
     * Generate a single Provider 1 employee payload matching schemas/provider1-schema.json
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function make(array $overrides = []): array
    {
        $faker = app(Faker::class);

        $firstName = $faker->firstName();
        $lastName = $faker->lastName();

        $payload = [
            'emp_id' => 'P1_' . $faker->unique()->numerify('###'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email_address' => strtolower($firstName . '.' . $lastName) . '@provider1.example',
            'phone' => $faker->e164PhoneNumber(),
            'job_title' => $faker->jobTitle(),
            'dept' => $faker->randomElement(['Security', 'Security Operations', 'Night Shift Security', 'Day Shift Security']),
            'hire_date' => $faker->date('Y-m-d', 'now'),
            'employment_status' => $faker->randomElement(['active', 'inactive', 'terminated']),
        ];

        return array_replace($payload, $overrides);
    }

    /**
     * Generate many Provider 1 employee payloads.
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



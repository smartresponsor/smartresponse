<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Faker\Factory;
use Faker\Generator;

abstract class AbstractFakerFixture extends Fixture
{
    protected function faker(): Generator
    {
        $faker = Factory::create('en_US');
        $faker->seed(crc32(static::class));

        return $faker;
    }
}

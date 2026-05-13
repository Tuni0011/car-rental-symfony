<?php

namespace App\DataFixtures;

use App\Entity\Car;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CarFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cars = [
            ['BMW', 'X5', 120, 'TN-1001'],
            ['Audi', 'A4', 90, 'TN-1002'],
            ['Mercedes', 'C-Class', 110, 'TN-1003'],
            ['Volkswagen', 'Golf', 60, 'TN-1004'],
            ['Range Rover', 'Evoque', 150, 'TN-1005'],
        ];

        foreach ($cars as $data) {
            $car = new Car();

            $car->setBrand($data[0]);
            $car->setModel($data[1]);
            $car->setPricePerDay($data[2]);
            $car->setStatus('available');
            $car->setRegistrationNumber($data[3]);

            $manager->persist($car);
        }

        $manager->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

use Faker\Factory;

/**
 *
 */
class CategoryFixture extends Fixture
{
    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $categories = [];

        $slugger = new AsciiSlugger();

        for ($i = 0; $i < 15; $i++) {
            $name = ucfirst($faker->words(rand(2, 3), true));
            $slug = $slugger->slug($name)->lower() . '-' . uniqid();

            $category = new Category();
            $category->setName($name)
                ->setSlug($slug)
                ->setDescription($faker->text(200))
                ->setArticle(ucfirst($faker->paragraphs(2, true)))
                ->setIsActive(rand(1, 100) <= 80);

            $manager->persist($category);
            $categories[] = $category;
        }

        for ($i = 0; $i < 15; $i++) {
            $name = ucfirst($faker->words(rand(2, 3), true));
            $slug = $slugger->slug($name)->lower() . '-' . uniqid();

            $category = new Category();
            $category->setName($name)
                ->setSlug($slug)
                ->setDescription($faker->text(200))
                ->setArticle(ucfirst($faker->paragraphs(2, true)))
                ->setIsActive(rand(1, 100) <= 80)
                ->setParent($categories[array_rand($categories)]);

            $manager->persist($category);
        }

        $manager->flush();
    }
}

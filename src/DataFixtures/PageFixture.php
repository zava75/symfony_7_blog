<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Page;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 *
 */
class PageFixture extends Fixture
{
    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $pages = [
            ['name' => 'Home', 'slug' => 'home', 'title' => 'Home Symfony 7 Blog'],
            ['name' => 'Contact', 'slug' => 'contact', 'title' => 'Contact Us'],
            ['name' => 'About', 'slug' => 'about', 'title' => 'About This Blog'],
        ];

        foreach ($pages as $pageData) {

            $page = new Page();
            $page->setName($pageData['name']);
            $page->setSlug($pageData['slug']);
            $page->setTitle($pageData['title']);
            $page->setDescription($faker->text(200));
            $page->setArticle($faker->paragraphs(nb: 5, asText: true));
            $page->setImage('demo/' . $faker->numberBetween(1, 30) . '.jpg');
            $page->setIsActive(true);

            $manager->persist($page);
        }

        $manager->flush();
    }
}

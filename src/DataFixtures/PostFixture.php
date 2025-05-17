<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Post;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Faker\Factory;

/**
 *
 */
class PostFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private UserRepository $userRepository
    ) {}

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $users = $this->userRepository->findAll();
        $categories = $this->categoryRepository->findWithParent();

        $slugger = new AsciiSlugger();

        if (count($users) < 1 || count($categories) < 1) {
            throw new \Exception('Not enough users or categories found');
        }

        for ($i = 1; $i <= 250; $i++) {

            $name = ucfirst($faker->words(rand(3, 5), true)) . ' ' . $i;
            $slug = $slugger->slug($name)->lower() . '-' . uniqid();

            $post = new Post();
            $post->setName($name);
            $post->setSlug($slug);
            $post->setTitle($name);
            $post->setImage($faker->boolean(90) ? 'demo/' . $faker->numberBetween(1, 30) . '.jpg' : null);
            $post->setDescription($faker->text(200));
            $post->setArticle($faker->paragraphs(nb: 5, asText: true));
            $post->setIsActive(true);
            $post->setCreatedAt($faker->dateTime);

            $post->setUser($users[array_rand($users)]);
            $post->setCategory($categories[array_rand($categories)]);

            $manager->persist($post);
        }

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @phpstan-return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            UserFixture::class,
            CategoryFixture::class,
        ];
    }
}
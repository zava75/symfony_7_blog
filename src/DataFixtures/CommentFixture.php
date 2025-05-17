<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Repository\PostRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 *
 */
class CommentFixture extends Fixture implements DependentFixtureInterface
{
    /**
     * @param PostRepository $postRepository
     */
    public function __construct(
        private readonly PostRepository $postRepository
    ) {}

    /**
     * Load data fixtures with the passed EntityManager
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $posts = $this->postRepository->findAll();


        if (count($posts) < 1) {
            throw new \Exception('No posts found');
        }

        for ($i = 1; $i <= 500; $i++) {

            $comment = new Comment();
            $comment->setAuthor($faker->name);
            $comment->setEmail($faker->email);
            $comment->setContent($faker->text);
            $comment->setPost($posts[array_rand($posts)]);
            $comment->setIsActive($isActive = $faker->boolean);
            $comment->setCreatedAt($faker->dateTime);

            $manager->persist($comment);
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
            PostFixture::class,
        ];
    }
}
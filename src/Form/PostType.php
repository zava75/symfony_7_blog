<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Post;
use Symfony\Component\Validator\Constraints\File;

/**
 *
 */
class PostType extends AbstractType
{
    /**
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)
            ->add('article', TextareaType::class, [
                'attr' => [
                    'rows' => 8,
                ],
            ])
            ->add('isActive')
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'query_builder' => fn(CategoryRepository $cr) => $cr->createActiveWithActiveParentQueryBuilder(),
                'required' => true,
            ])
            ->add('image', FileType::class, [
                'label' => 'Post image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/png, image/jpeg, image/jpg, image/webp'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                            'image/webp'
                        ]
                    ])
                ]

            ]);

        if ($options['is_edit']) {
            $builder->add('remove_image', CheckboxType::class, [
                'label' => 'Remove current image',
                'required' => false,
                'mapped' => false,
            ]);
        }
    }

    /**
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'is_edit' => false,
            ]);
    }
}


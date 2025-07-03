<?php

namespace App\Form;

use App\Entity\Board;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File as FileConstraint;


class PostForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du sujet',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre de votre sujet...',
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Écrivez votre message...'
                    ]
                ])

            ->add('board', EntityType::class, [
                'class' => Board::class,
                'choice_label' => 'name',
                'label' => 'Forum',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Choisissez un forum...',
                'group_by' => function (Board $board) {
                    $categories = $board->getCategory(); // méthode ManyToMany
                    if ($categories->isEmpty()) {
                        return 'Sans catégorie';
                    }
                    return $categories->first()->getName();
                }
            ]);

        // Que lors de la création, pas à la modification
        if (!$options['is_edit']) {
            $builder->add('board', EntityType::class, [
                'class' => Board::class,
                'choice_label' => 'name',
                'label' => 'Forum',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Choisissez un forum...',
                'group_by' => function(Board $board) {
                    $categories = $board->getCategory();
                    if ($categories->isEmpty()) {
                        return 'Sans catégorie';
                    }
                    // Prend le nom de la première catégorie
                    return $categories->first()->getName();
                },
            ]);
        }

        //Que lors de la modification
        if (!$options['is_edit']) {
            $builder->add('board', EntityType::class, [
                'class' => Board::class,
                'choice_label' => 'name',
                'label' => 'Forum',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Choisissez un forum...'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'is_edit' => false,
        ]);
    }
}

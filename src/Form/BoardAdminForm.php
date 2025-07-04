<?php

namespace App\Form;

use App\Entity\Board;
use App\Entity\Role;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class BoardAdminForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du board',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Questions générales, Aide Symfony...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom du board ne peut pas être vide.'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ])
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégories',
                'multiple' => true,
                'expanded' => true, // Cases à cocher
                'required' => false,
                'help' => 'Sélectionnez les catégories auxquelles ce board appartient.',
                'attr' => [
                    'class' => 'form-check-container'
                ],
                'choice_attr' => function($choice, $key, $value) {
                    return [
                        'class' => 'form-check-input'
                    ];
                },
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
            ])
            ->add('allowedRoles', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'name',
                'label' => 'Rôles autorisés à voir ce board',
                'multiple' => true,
                'expanded' => true, // Cases à cocher
                'mapped' => false, // Non mappé car on gère nous-mêmes la relation
                'required' => false,
                'help' => 'Sélectionnez les rôles qui pourront voir ce board.',
                'attr' => [
                    'class' => 'row g-2'
                ],
                'choice_attr' => function($choice, $key, $value) {
                    return [
                        'class' => 'form-check-input'
                    ];
                },
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Board::class,
        ]);
    }
}

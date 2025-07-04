<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class CategoryForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la catégorie',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Développement, Marketing...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom de la catégorie ne peut pas être vide.'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ])
                ]
            ])
            ->add('allowedRoles', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'name',
                'label' => 'Rôles autorisés à voir cette catégorie',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => false,
                'help' => 'Sélectionnez les rôles qui pourront voir cette catégorie.'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Créer la catégorie',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}

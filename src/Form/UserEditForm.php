<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEditForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre nom d\'utilisateur'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez remplir le nom d\'utilisateur']),
                    new Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le nom d\'utilisateur doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom d\'utilisateur ne doit pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@insider.fr'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez remplir votre adresse email']),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'L\'adresse doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'L\'adresse ne doit pas dépasser {{ limit }} caractères'
                    ])
                ],
                'help' => 'Utilisez une adresse @insider.fr, @collaborator.fr ou @external.fr'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

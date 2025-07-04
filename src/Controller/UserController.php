<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Entity\Role;
use App\Form\UserChangePassForm;
use App\Form\UserRegistrationForm;
use App\Form\UserEditForm;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        if ($this->getUser()) { //si connecté
            return $this->redirectToRoute('app_home');
        }

        $categories = $entityManager->getRepository(Category::class)->findAll();

        $user = new User();
        $form =  $this->createForm(UserRegistrationForm::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var string $password */
                $password = $form->get('password')->get('first')->getData();
                $user->setPassword($userPasswordHasher->hashPassword($user, $password));

                $this->assignRoleByMail($user, $entityManager);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès !');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du compte : ' . $e->getMessage());
            }
        }
        return $this->render('user/register.html.twig', [
            'UserRegistrationForm' => $form,
            'categories' => $categories,
        ]);
    }

    private function assignRoleByMail(User $user, EntityManagerInterface $entityManager): void
    {
        $email = $user->getEmail();
        $roleRepository = $entityManager->getRepository(Role::class);

        if (str_ends_with($email, '@insider.fr')) {
            $roleId = 1; // INSIDER
        } elseif (str_ends_with($email, '@collaborator.fr')) {
            $roleId = 2; // COLLABORATION
        } elseif (str_ends_with($email, '@external.fr')) {
            $roleId = 3; // EXTERNE
        } elseif (str_ends_with($email, '@admin.fr')) {
            $roleId = 4;
        }

        $role = $roleRepository->find($roleId);

        if ($role) {
            $user->setIdRole($role);
        } else {
            throw new \Exception("Le rôle avec l'ID {$roleId} n'existe pas en base de données.");
        }
    }

    #[Route('/profile', name: 'user_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(CommentRepository $messageList, PostRepository $postRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        $user = $this->getUser();
        $userMessage = $messageList->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            10
        );

        $userPosts = $postRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        $totalMessages = $messageList->count(['user' => $user]);

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'userMessage' => $userMessage,
            'totalMessages' => $totalMessages,
            'userPosts' => $userPosts,
            'categories' => $categories,
        ]);
    }

    #[Route('/user-edit', name: 'user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserEditForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {

                $this->entityManager->flush();
                $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

                return $this->redirectToRoute('user_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur s\'est produite lors de la modification.');
            }
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/change-pass', name: 'user_change_password', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager  ): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserChangePassForm::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            if (!$userPasswordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->render('user/change_password.html.twig', [
                    'form' => $form,
                ]);
            }
            try {
                $newPassword = $form->get('plainPassword')->get('first')->getData();
                $hashedPassword = $userPasswordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);

                $this->entityManager->flush();
                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

                return $this->redirectToRoute('user_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur s\'est produite lors du changement de mot de passe.');
            }
        }
        return $this->render('user/change_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/profile/posts', name: 'user_posts', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function userPosts(PostRepository $postRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        $user = $this->getUser();

        $userPosts = $postRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
        return $this->render('user/posts.html.twig', [
            'user' => $user,
            'userPosts' => $userPosts,
            'categories' => $categories,
        ]);
    }

    #[Route('/profile/comments', name: 'user_comments', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function userComments(CommentRepository $messageList, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        $user = $this->getUser();

        $userMessage = $messageList->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
        );

        return $this->render('user/comments.html.twig', [
            'user' => $user,
            'userMessage' => $userMessage,
            'categories' => $categories,
        ]);
    }

    #[Route('/admin', name: 'app_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        return $this->render('admin/index.html.twig');
    }

}

<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\Category;
use App\Entity\Permission;
use App\Entity\User;
use App\Entity\Role;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_admin')]
    public function index(): Response
    {

        return $this->render('admin/index.html.twig');
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'categories' => $categories,
        ]);
    }

    #[Route('/users/{id}/toggle-block', name: 'admin_user_toggle_block', methods: ['POST'])]
    public function toggleUserBlock(User $user): Response
    {

        $user->setBlocked(!$user->isBlocked());
        $this->entityManager->flush();

        $status = $user->isBlocked() ? 'bloqué' : 'débloqué';
        $this->addFlash('success', "L'utilisateur {$user->getPseudo()} a été {$status}.");

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/change-role', name: 'admin_user_change_role', methods: ['POST'])]
    public function changeUserRole(User $user, Request $request) : Response
    {
        $roleId = $request->request->get('role_id');
        $roleRepository = $this->entityManager->getRepository(\App\Entity\Role::class);
        $role = $roleRepository->find($roleId);

        $user->setIdRole($role);
        $this->entityManager->flush();
        $this->addFlash('success', "Le rôle de {$user->getPseudo()} a été modifié vers {$role->getName()}.");

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/categories', name: 'admin_categories')]
    public function categories(): Response
    {
        $categoryRepository = $this->entityManager->getRepository(\App\Entity\Category::class);
        $permissionRepository = $this->entityManager->getRepository(\App\Entity\Permission::class);
        $roleRepository = $this->entityManager->getRepository(\App\Entity\Role::class);

        $categories = $categoryRepository->findAll();

        $categoriesWithPermissions = [];
        foreach ($categories as $category) {
            $permissions = $permissionRepository->findByEntity('category', $category->getId());

            $roleNames = [];
            foreach ($permissions as $permission) {
                $role = $roleRepository->find($permission->getRoleId());
                if ($role) {
                    $roleNames[] = $role->getName();
                }
            }

            $categoriesWithPermissions[] = [
                'categories' => $categories,
                'category' => $category,
                'permissions' => $permissions,
                'roleNames' => $roleNames
            ];
        }
        return $this->render('admin/categories.html.twig', [
            'categoriesWithPermissions' => $categoriesWithPermissions,
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/{id}/edit', name: 'admin_category_edit')]
    public function editCategory(Category $category, Request $request): Response
    {
        $form = $this->createForm(\App\Form\CategoryAdminForm::class, $category);
        $permissionRepository = $this->entityManager->getRepository(Permission::class);
        $roleRepository = $this->entityManager->getRepository(Role::class);

        $currentPermissions = $permissionRepository->createQueryBuilder('p')
            ->where('p.entity_type = :type')
            ->andWhere('p.entity_id = :id')
            ->setParameter('type', 'category')
            ->setParameter('id', $category->getId())
            ->getQuery()
            ->getResult();

        $currentRoles = [];
        foreach ($currentPermissions as $permission) {
            $role = $roleRepository->find($permission->getRoleId());
            if ($role) {
                $currentRoles[] = $role;
            }
        }

        $form->get('allowedRoles')->setData($currentRoles);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Supprimer les anciennes permissions
            foreach ($currentPermissions as $permission) {
                $this->entityManager->remove($permission);
            }

            // Ajouter les nouvelles permissions
            $allowedRoles = $form->get('allowedRoles')->getData();
            foreach ($allowedRoles as $role) {
                $permission = new \App\Entity\Permission();
                $permission->setEntityType('category');
                $permission->setEntityId($category->getId());
                $permission->setRoleId($role->getId());

                $this->entityManager->persist($permission);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Catégorie modifiée avec succès.');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/category_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier la catégorie',
            'category' => $category,
            'categories' => $category,
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Category $category, Request $request): Response
    {
        try {
            // Supprimer les permissions associées
            $permissionRepository = $this->entityManager->getRepository(\App\Entity\Permission::class);
            $permissions = $permissionRepository->findByEntity('category', $category->getId());

            foreach ($permissions as $permission) {
                $this->entityManager->remove($permission);
            }

            // Supprimer la catégorie
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            $this->addFlash('success', 'Catégorie supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression de la catégorie.');
        }

        return $this->redirectToRoute('admin_categories');
    }


    #[Route('/boards', name: 'admin_boards')]
    public function boards(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $boardRepository = $this->entityManager->getRepository(Board::class);
        $permissionRepository = $this->entityManager->getRepository(Permission::class);
        $roleRepository = $this->entityManager->getRepository(Role::class);

        $boards = $boardRepository->findAll();

        $boardsWithPermissions = [];
        foreach ($boards as $board) {
            $permissions = $permissionRepository->findByEntity('board', $board->getId());

            $roleNames = [];
            foreach ($permissions as $permission) {
                $role = $roleRepository->find($permission->getRoleId());
                if ($role) {
                    $roleNames[] = $role->getName();
                }
            }

            $boardsWithPermissions[] = [
                'board' => $board,
                'permissions' => $permissions,
                'roleNames' => $roleNames
            ];
        }
        return $this->render('admin/boards.html.twig', [
            'boardsWithPermissions' => $boardsWithPermissions,
            'categories' => $categories,
        ]);
    }

    #[Route('/boards/{id}/edit', name: 'admin_board_edit')]
    public function editBoard(Board $board, Request $request, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $form = $this->createForm(\App\Form\BoardAdminForm::class, $board);

        $permissionRepository = $this->entityManager->getRepository(Permission::class);
        $roleRepository = $this->entityManager->getRepository(Role::class);

        $currentPermissions = $permissionRepository->findByEntity('board', $board->getId());

        $currentRoles = [];
        foreach ($currentPermissions as $permission) {
            $role = $roleRepository->find($permission->getRoleId());
            if ($role) {
                $currentRoles[] = $role;
            }
        }

        $form->get('allowedRoles')->setData($currentRoles);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Supprimer les anciennes permissions
            foreach ($currentPermissions as $permission) {
                $this->entityManager->remove($permission);
            }

            // Ajouter les nouvelles permissions
            $allowedRoles = $form->get('allowedRoles')->getData();
            foreach ($allowedRoles as $role) {
                $permission = new \App\Entity\Permission();
                $permission->setEntityType('board');
                $permission->setEntityId($board->getId());
                $permission->setRoleId($role->getId());

                $this->entityManager->persist($permission);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Board modifié avec succès.');
            return $this->redirectToRoute('admin_boards');
        }

        return $this->render('admin/board_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le board',
            'board' => $board,
            'categories' => $categories,
        ]);
    }

    #[Route('/boards/{id}/delete', name: 'admin_board_delete', methods: ['POST'])]
    public function deleteBoard(Board $board, Request $request): Response
    {
        try {
            // Supprimer les permissions associées
            $permissionRepository = $this->entityManager->getRepository(\App\Entity\Permission::class);
            $permissions = $permissionRepository->findByEntity('board', $board->getId());

            foreach ($permissions as $permission) {
                $this->entityManager->remove($permission);
            }

            // Supprimer la catégorie
            $this->entityManager->remove($board);
            $this->entityManager->flush();

            $this->addFlash('success', 'Board supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du board.');
        }

        return $this->redirectToRoute('admin_boards');
    }
}

<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\Category;
use App\Entity\Permission;
use App\Entity\User;
use App\Entity\Role;
use App\Form\BoardAdminForm;
use App\Form\CategoryAdminForm;
use App\Repository\CategoryRepository;
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
    public function users(UserRepository $userRepository, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();

        if ($user) {
            $roleId = $user->getIdRole()?->getId();
            $categories = $categoryRepository->findAuthorizedCategory($roleId);
        } else {
            $categories = $categoryRepository->findAuthorizedCategory(null);
        }
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
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/categories', name: 'admin_categories')]
    public function categories(): Response
    {
        $categoryRepository = $this->entityManager->getRepository(\App\Entity\Category::class);
        $permissionRepository = $this->entityManager->getRepository(\App\Entity\Permission::class);
        $roleRepository = $this->entityManager->getRepository(\App\Entity\Role::class);

        $user = $this->getUser();

        if ($user) {
            $roleId = $user->getIdRole()?->getId();
            $categories = $categoryRepository->findAuthorizedCategory($roleId);
        } else {
            $categories = $categoryRepository->findAuthorizedCategory(null);
        }

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

    #[Route('/categories/new',  name: 'admin_category_new')]
    public function NewCategory(Request $request, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();

        if ($user) {
            $roleId = $user->getIdRole()?->getId();
            $categories = $categoryRepository->findAuthorizedCategory($roleId);
        } else {
            $categories = $categoryRepository->findAuthorizedCategory(null);
        }
        $category = new Category();
        $form = $this->createForm(CategoryAdminForm::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->persist($category);
                $this->entityManager->flush();

                $allowedRoles = $form->get('allowedRoles')->getData();
                $roleIds= [];

                foreach ($allowedRoles as $role) {
                    $roleIds[] = $role->getId();
                }

                if (!in_array(4, $roleIds)) {
                    $adminPermission = new Permission();
                    $adminPermission->setEntityType('category');
                    $adminPermission->setEntityId($category->getId());
                    $adminPermission->setRoleId(4); // Admin par défaut
                    $this->entityManager->persist($adminPermission);
                }

                foreach ($allowedRoles as $role) {
                    $permission = new Permission();
                    $permission->setEntityType('category');
                    $permission->setEntityId($category->getId());
                    $permission->setRoleId($role->getId());
                    $this->entityManager->persist($permission);
                }

                $this->entityManager->flush();

                return $this->redirectToRoute('admin_categories');

        }
        return $this->render('admin/category_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouvelle catégorie',
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/{id}/edit', name: 'admin_category_edit')]
    public function editCategory(Category $category, Request $request, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();

        if ($user) {
            $roleId = $user->getIdRole()?->getId();
            $categories = $categoryRepository->findAuthorizedCategory($roleId);
        } else {
            $categories = $categoryRepository->findAuthorizedCategory(null);
        }
        $form = $this->createForm(CategoryAdminForm::class, $category);
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

            $allowedRoles = $form->get('allowedRoles')->getData();
            $roleIds = [];

            foreach ($allowedRoles as $role) {
                $roleIds[] = $role->getId();
            }

            if (!in_array(4, $roleIds)) {
                $adminPermission = new Permission();
                $adminPermission->setEntityType('category');
                $adminPermission->setEntityId($category->getId());
                $adminPermission->setRoleId(4); // Admin par défaut
                $this->entityManager->persist($adminPermission);
            }

            // Ajouter les nouvelles permissions
            foreach ($allowedRoles as $role) {
                $permission = new Permission();
                $permission->setEntityType('category');
                $permission->setEntityId($category->getId());
                $permission->setRoleId($role->getId());
                $this->entityManager->persist($permission);
            }

            $this->entityManager->flush();
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/category_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier la catégorie',
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Category $category, Request $request): Response
    {

            // Supprimer les permissions associées
            $permissionRepository = $this->entityManager->getRepository(\App\Entity\Permission::class);
            $permissions = $permissionRepository->findByEntity('category', $category->getId());

            foreach ($permissions as $permission) {
                $this->entityManager->remove($permission);
            }

            // Supprimer la catégorie
            $this->entityManager->remove($category);
            $this->entityManager->flush();



        return $this->redirectToRoute('admin_categories');
    }


    #[Route('/boards', name: 'admin_boards')]
    public function boards(CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();

        if ($user) {
            $roleId = $user->getIdRole()?->getId();
            $categories = $categoryRepository->findAuthorizedCategory($roleId);
        } else {
            $categories = $categoryRepository->findAuthorizedCategory(null);
        }
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

    #[Route('/boards/new',  name: 'admin_board_new')]
    public function NewBoard(Request $request, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();

        if ($user) {
            $roleId = $user->getIdRole()?->getId();
            $categories = $categoryRepository->findAuthorizedCategory($roleId);
        } else {
            $categories = $categoryRepository->findAuthorizedCategory(null);
        }
        $board = new Board();
        $form = $this->createForm(BoardAdminForm::class, $board);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
                $selectedCategories = $form->get('category')->getData();
                $this->entityManager->persist($board);
                $this->entityManager->flush();
                $permissionRepository = $this->entityManager->getRepository(Permission::class);

                $allowedRoleIds = [];
                foreach ($selectedCategories as $category) {
                    $categoryPermissions = $permissionRepository->findByEntity('category', $category->getId());
                    foreach ($categoryPermissions as $permission) {
                        $allowedRoleIds[] = $permission->getRoleId();
                    }
                }
                //Supression doublon
                $allowedRoleIds = array_unique($allowedRoleIds);

                foreach ($allowedRoleIds as $roleId) {
                    $permission = new Permission();
                    $permission->setEntityType('board');
                    $permission->setEntityId($board->getId());
                    $permission->setRoleId($roleId);
                    $this->entityManager->persist($permission);
                }

                $this->entityManager->flush();

                return $this->redirectToRoute('admin_boards');

        }
        return $this->render('admin/board_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouveau Board',
            'categories' => $categories,
        ]);
    }

    #[Route('/boards/{id}/edit', name: 'admin_board_edit')]
    public function editBoard(Board $board, Request $request, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();

        if ($user) {
            $roleId = $user->getIdRole()?->getId();
            $categories = $categoryRepository->findAuthorizedCategory($roleId);
        } else {
            $categories = $categoryRepository->findAuthorizedCategory(null);
        }
        $form = $this->createForm(BoardAdminForm::class, $board);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $permissionRepository = $this->entityManager->getRepository(Permission::class);
            $oldPermissions = $permissionRepository->findByEntity('board', $board->getId());
            foreach ($oldPermissions as $permission) {
                $this->entityManager->remove($permission);
            }

            $selectedCategories = $form->get('category')->getData();

            $allowedRoleIds = [];
            foreach ($selectedCategories as $category) {
                $categoryPermissions = $permissionRepository->findByEntity('category', $category->getId());
                foreach ($categoryPermissions as $permission) {
                    $allowedRoleIds[] = $permission->getRoleId();
                }
            }
            $allowedRoleIds = array_unique($allowedRoleIds);
            foreach ($allowedRoleIds as $roleId) {
                $permission = new Permission();
                $permission->setEntityType('board');
                $permission->setEntityId($board->getId());
                $permission->setRoleId($roleId);
                $this->entityManager->persist($permission);
            }

            $this->entityManager->flush();

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

            // Supprimer les permissions associées
            $permissionRepository = $this->entityManager->getRepository(\App\Entity\Permission::class);
            $permissions = $permissionRepository->findByEntity('board', $board->getId());

            foreach ($permissions as $permission) {
                $this->entityManager->remove($permission);
            }

            // Supprimer la catégorie
            $this->entityManager->remove($board);
            $this->entityManager->flush();


        return $this->redirectToRoute('admin_boards');
    }
}

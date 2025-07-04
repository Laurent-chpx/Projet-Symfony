<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\File;
use App\Entity\Permission;
use App\Entity\Post;
use App\Form\CommentForm;
use App\Form\PostForm;
use App\Repository\BoardRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForumController extends AbstractController
{
    #[Route('/forum', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        // Récupération des 5 posts les plus récents
        $latestPosts = $entityManager->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $user = $this->getUser();

        if ($user) {
            $roleId = $user->getIdRole()?->getId();
            $categories = $categoryRepository->findAuthorizedCategory($roleId);
        } else {
            $categories = $categoryRepository->findAuthorizedCategory(null);
        }


        return $this->render('forum/index.html.twig', [
            'posts' => $latestPosts,
            'categories' => $categories,
            'user' => $user,
        ]);
    }


    // Affichage des boards par catégorie selon l'id de la catégorie dans l'URL
    #[Route('/forum/categories/{id}', name: 'app_categories')]
    public function categories(int $id, CategoryRepository $repository): Response
    {
        $category = $repository->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée.');
        }

        $boards = $category->getBoards();
        $allCategories = $repository->findAll();

        return $this->render('forum/categories.html.twig', [
            'category' => $category,
            'boards' => $boards,
            'categories' => $allCategories,
        ]);
    }

    // Affichage de tous les posts d'un board
    #[Route('/forum/boards/{id}', name: 'app_board_show')]
    public function boards(int $id, PostRepository $postRepository, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();

        $posts = $postRepository->createQueryBuilder('p')
            ->leftJoin('p.board', 'b')
            ->addSelect('b')
            ->leftJoin('p.comments', 'c')
            ->addSelect('c')
            ->where('b.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();


        return $this->render('forum/boards.html.twig', [
            'controller_name' => 'ForumController',
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }

    // Affichage d'un post avec ses commentaires, le nom des utilisateurs ayant commenté et la date des commentaires
    // Formulaire de commentaire également présent, avec la possibilité d'ajouter une ou plusieurs pièces jointes (optionnel)
    #[Route('/forum/post/{id}', name: 'app_post_show', requirements: ['id' => '\d+'])]
    public function posts(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();

        $posts = $entityManager->getRepository(Post::class)->createQueryBuilder('p')
            ->leftJoin('p.comments', 'c')
            ->addSelect('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        if (!$posts) {
            throw $this->createNotFoundException('Post non trouvé');
        }

        $post = $posts[0];

        $comment = new Comment();
        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUser($this->getUser());
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setPost($post);

            $uploadedFiles = $form->get('files')->getData();

            if ($uploadedFiles) {
                foreach ($uploadedFiles as $uploadedFile) {
                    if (!$uploadedFile instanceof UploadedFile) {
                        continue;
                    }

                    $originalName = $uploadedFile->getClientOriginalName();

                    $uploadedFile->move(
                        $this->getParameter('files_directory'),
                        $originalName
                    );

                    $fileEntity = new File();
                    $fileEntity->setNameOriginal($originalName);
                    $fileEntity->setNameHashed($originalName);
                    $fileEntity->setComment($comment);

                    $comment->addFile($fileEntity);
                    $entityManager->persist($fileEntity);
                }
            }

            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('forum/posts.html.twig', [
            'posts' => $posts,
            'categories' => $categories,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forum/post/create', name: 'app_post_create')]
    public function createPosts(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        $post = new Post();
        $form = $this->createForm(PostForm::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTimeImmutable());

            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('forum/posts-create.html.twig', [
            'categories' => $categories,
            'form' => $form->createView(),
        ]);
    }
}

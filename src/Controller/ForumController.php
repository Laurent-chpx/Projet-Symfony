<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\BoardRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForumController extends AbstractController
{
    #[Route('/forum', name: 'app_home')]
    public function index( PostRepository $postRepository, CommentRepository $commentRepository): Response
    {
        $recentPosts = $postRepository->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.board', 'b')
            ->addSelect('u', 'b')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

            $postsWithComments = [];
            foreach ($recentPosts as $post) {
                $recentComments = $commentRepository->createQueryBuilder('c')
                ->leftJoin('c.user', 'u')
                ->addSelect('u')
                ->where('c.post = :post')
                ->setParameter('post', $post)
                ->orderBy('c.id', 'DESC')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();

                $postsWithComments[] = [
                    'post' => $post,
                    'comments' => $recentComments
                ];

            }

        return $this->render('forum/index.html.twig', [
            'controller_name' => 'ForumController',
            'postsWithComments' => $postsWithComments,
        ]);
    }

    #[Route('/forum/categories', name: 'app_categories')]
    public function categories(CategoryRepository $repository): Response
    {

        $categories = $repository->createQueryBuilder('c')
            ->leftJoin('c.boards', 'b')
            ->addSelect('b')
            ->getQuery()
            ->getResult();

        return $this->render('forum/categories.html.twig', [
            'controller_name' => 'ForumController',
            'categories' => $categories,
        ]);
    }

    #[Route('/forum/boards/{id}', name: 'app_board_show')]
    public function boards(int $id, PostRepository $postRepository): Response
    {
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
        ]);
    }

    #[Route('/forum/post/{id}', name: 'app_post_show')]
    public function posts(int $id, PostRepository $postRepository): Response
    {
        $posts = $postRepository->createQueryBuilder('p')
            ->leftJoin('p.comments', 'c')
            ->addSelect('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        return $this->render('forum/posts.html.twig', [
            'controller_name' => 'ForumController',
            'posts' => $posts,
        ]);
    }
}

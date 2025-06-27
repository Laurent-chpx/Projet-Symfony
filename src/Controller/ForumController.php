<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForumController extends AbstractController
{
    #[Route('/forum', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('forum/index.html.twig', [
            'controller_name' => 'ForumController',
        ]);
    }

    #[Route('/forum/categories', name: 'app_categories')]
    public function categories(CategoryRepository $repository): Response
    {
        $categories = $repository->findAll();

        return $this->render('forum/categories.html.twig', [
            'controller_name' => 'ForumController',
            'categories' => $categories,
        ]);
    }
}

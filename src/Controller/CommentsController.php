<?php

namespace App\Controller;
use App\Entity\Comment;
use App\Entity\File;
use App\Form\CommentForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentsController extends AbstractController
{
    #[Route('/forum/comments-add', name: 'app_comments_add')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUser($this->getUser());
            $comment->setCreatedAt(new \DateTimeImmutable());
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
                    $em->persist($fileEntity);
                }
            }


            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('app_home', [
            ]);
        }

        return $this->render('forum/comment-create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

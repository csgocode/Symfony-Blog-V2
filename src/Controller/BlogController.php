<?php

namespace App\Controller;

use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentFormType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\PostFormType;
use App\Form\PostNewFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class BlogController extends AbstractController
{
    #[Route("/blog/buscar/{page}", name: 'blog_buscar')]
    public function buscar(ManagerRegistry $doctrine,  Request $request, int $page = 1): Response
    {
       return new Response("Buscar");
    } 
   
    #[Route("/blog/new", name: 'new_post')]
    public function newPost(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $formulario = $this->createForm(PostFormType::class, $post);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            $file = $formulario->get('Image')->getData();
        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            $post->setImage($newFilename);
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/blog';
            $file->move($uploadDir, $newFilename);
        }

            $post = $formulario->getData();
            $entityManager = $doctrine->getManager();
            $slug = $slugger->slug($post->getTitle());
            $post->setSlug($slug);
            $post->setNumLikes(0);
            $post->setNumComments(0);
            $post->setNumViews(0);
            $entityManager->persist($post);
            $entityManager->flush();
            return $this->redirectToRoute('blog', []);
        }
    
        return $this->render('/blog/new_post.html.twig', array(
    
            'form' => $formulario->createView()
    
        ));
    
    }

    
    
    #[Route("/single_post/{slug}/like", name: 'post_like')]
    public function like(ManagerRegistry $doctrine, $slug): Response
    {
        return new Response("like");

    }

    #[Route("/blog", name: 'blog')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $posts = $repository->findAll();
        
        return $this->render('blog/blog.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route("/single_post/{slug}", name: 'single_post')]
    public function post(ManagerRegistry $doctrine, Request $request, string $slug): Response
    {
    $repository = $doctrine->getRepository(Post::class);
    $post = $repository->findOneBy(['Slug' => $slug]);

    if (!$post) {
        throw $this->createNotFoundException('El post solicitado no existe.');
    }

    $comment = new Comment();
    $formulario = $this->createForm(CommentFormType::class, $comment);
    $formulario->handleRequest($request);

    if ($formulario->isSubmitted() && $formulario->isValid()) {
        $comment->setPost($post);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($comment);
        $entityManager->flush();
    }

    $comRep = $doctrine->getRepository(Comment::class);
    $comentarios = $comRep->findBy(['post' => $post]);

    return $this->render('blog/single_post.html.twig', [
        'post' => $post,
        'comentarios' => $comentarios,
        'commentForm' => $formulario->createView(),
    ]);
}


}

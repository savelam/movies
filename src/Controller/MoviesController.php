<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\MovieFormType;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MoviesController extends AbstractController
{

    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    
    #[Route('/movies', name: 'app_movies')]
    public function index(): Response
    {
        $repository =  $this->em->getRepository(Movie::class);
        // findAll()
        $movies = $repository->findAll();

        

        /* dd($movies); */
        return $this->render("movies/index.html.twig",[
            'movies'=>$movies,
        ]);
        
    }

     #[Route('/movies/create', name: 'create_movies')]
     public function create(Request $request): Response
     {
        $movie = new Movie();

        $form = $this->createForm(MovieFormType::class, $movie);


        // handle form request
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $newMovie = $form->getData();

            $imagePath = $form->get('imagePath')->getData();
            if($imagePath){
                $newFileName = uniqid().'.'.$imagePath->guessExtension();

                try{
                    $imagePath->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads',
                        $newFileName
                    );

                    
                }catch(FileException $e){
                    return new Response($e->getMessage());
                }

                // save to database
                $newMovie->setImagePath('/uploads/'.$newFileName);
                
            }
            
            $this->em->persist($newMovie);
            $this->em->flush();

            return $this->redirectToRoute('app_movies');
            
            // dd($newMovie);
            // exit;
        }
        return $this->render("movies/create.html.twig",[
            'form' => $form->createView(),
        ]);
        
     }


     #[Route('/movies/edit/{id}',name:'edit_movie')]
     public function edit($id,Request $request): Response
     {
        $movie = $this->em->getRepository(Movie::class)->find($id);
        $form = $this->createForm(MovieFormType::class,$movie);

        $form->handleRequest($request);
        $imagePath = $form->get('imagePath')->getData();
        
        if($form->isSubmitted() && $form->isValid()){
            if($imagePath){
                if($movie->getImagePath() !== null){
                    if(file_exists(
                        $this->getParameter('kernel.project_dir').$movie->getImagePath()
                    )){
                        // unlink the file
                        unlink($this->getParameter('kernel.project_dir').$movie->getImagePath());
                        
                    }

                    $newFileName = uniqid().'.'.$imagePath->guessExtension();

                try{
                    $imagePath->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads',
                        $newFileName
                    );

                    
                }catch(FileException $e){
                    return new Response($e->getMessage());
                }

                // save to database
                $movie->setImagePath('/uploads/'.$newFileName);
                $this->em->flush();
                return $this->redirectToRoute('app_movies');
                }
            }else{
                $movie->setTitle($form->get('title')->getData());
                $movie->setReleaseYear($form->get('releaseYear')->getData());
                $movie->setDescription($form->get('description')->getData());
                
                $this->em->flush();
                return $this->redirectToRoute('app_movies');
            }
        }

        
        return $this->render('movies/edit.html.twig',[
            'movie'=>$movie,
            'form'=> $form->createView()
        ]);
     }

    #[Route('/movies/{id}', methods:['GET'],name: 'show_movie')]
    public function show($id): Response
    {
        $repository =  $this->em->getRepository(Movie::class);
        // findAll()
        $movie = $repository->find(array('id'=>$id));

        

        /* dd($movies); */
        return $this->render("movies/show.html.twig",[
            'movie'=>$movie,
        ]);
        
    }



     #[Route('/movies/delete/{id}', methods:['GET','DELETE'],name: 'delete_movie')]
    public function delete($id): Response
    {
        $movie =  $this->em->getRepository(Movie::class)->find($id);
        
        $this->em->remove($movie);
        $this->em->flush();
        return $this->redirectToRoute('app_movies');
        
    }

  
}
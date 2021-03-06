<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Trick;
use App\Entity\Video;
use App\Form\CommentType;
use App\Form\TrickType;
use App\Repository\CommentRepository;
use App\Repository\ImageRepository;
use App\Repository\TrickRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class TrickController extends AbstractController
{
    /**
     * @Route("/trick", name="add/trick")
     */
    public function index(TrickType $trickType, Request $request, EntityManagerInterface $manager)
    {

        $form = $this->createForm(TrickType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $images = $form->get('images')->getData();
            $trick = $form->getData();
            foreach($images as $image)
            {
                $fichier = md5(uniqid()).'.'.$image->guessExtension();
                $image->move($this->getParameter('images_directory'), $fichier
                );
                $img = new Image();
                $img->setName($fichier);
                $trick->addImage($img);
            }
            $user = $this->getUser();
            $creator = $user->getUsername();
            $trick->setCreator($creator);
            try{
                $manager -> persist ( $trick );
                $manager -> flush ();
                $this->addFlash('success', 'Cool ! your trick is created. Thank\'s from the community');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'nope, this name already exists...try with an other one.');
                return $this->redirectToRoute('home');
            }


            return $this->redirectToRoute('home');

        }
        return $this->render('trick/add.html.twig', array('form'=>$form->createView()));
    }

    /**
     * @Route("/show/{id}/{slug}", name="show", requirements={"slug"=".+"})
     * @param TrickRepository $trickRepository
     * @param $id
     * @param ImageRepository $imageRepository
     * @param VideoRepository $videoRepository
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @param CommentRepository $commentRepository
     *
     */
    public function show(TrickRepository $trickRepository, $id, ImageRepository $imageRepository,UserRepository $userRepository ,VideoRepository $videoRepository, EntityManagerInterface $manager, Request $request, CommentRepository $commentRepository)
    {
        $form = $this->createForm(CommentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $comment = $form->getData();
            $user = $this->getUser();
            $author = $user->getUsername();
            $comment->setAuthor($author);
            $comment->setTrick($id);
            $manager->persist($comment);
            $manager->flush();
        }
        $featuredImage = $trickRepository->showFeaturedImage($id);
        $hope = $trickRepository->commentAndProfil($id);
        $comments = $commentRepository->findByExampleField($id);
        $tricks = $trickRepository->findBy(array('id'=>$id));
        $images = $imageRepository->findBy(array('trick'=>$id));
        $video = $videoRepository->findBy(array('trick'=>$id));
        $userImageId = $userRepository->findBy(array('id'=>$id));
        return $this->render('trick/show.html.twig', array('featuredImage'=>$featuredImage,'trick'=>$tricks,'hopes'=>$hope,'comments'=>$comments,'userImageId'=>$userImageId ,'images'=>$images, 'videos'=>$video,'form'=>$form->createView()));
    }


    /**
     * @Route("remove/{id}", name="remove")
     */
    public function remove(EntityManagerInterface $manager, Trick $trick)
    {
        $manager->remove($trick);
        $manager->flush();
        $this->addFlash('successRemoveId', 'Your trick has been deleted !');
        return $this->redirectToRoute('home');
    }


    /**
     * @Route("removeImg/{id}", name="removeImg")
     * @param EntityManagerInterface $manager
     * @param Trick $trick
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeImg(EntityManagerInterface $manager, Image $image)
    {
        $manager->remove($image);
        $manager->flush();
        $this->addFlash('successRemoveImage', 'Your picture has been deleted !');
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("removeVid/{id}", name="removeVid")
     * @param EntityManagerInterface $manager
     * @param Trick $trick
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeVid(EntityManagerInterface $manager, Video $video)
    {
        $manager->remove($video);
        $manager->flush();
        $this->addFlash('successRemoveVideo', 'Your video has been deleted !');
        return $this->redirectToRoute('/');
    }


    /**
     * @Route("edit/{id}/{slug}", name="edit", requirements={"slug"=".+"})
     */
    public function edit(EntityManagerInterface $manager, Request $request,TrickRepository $trickRepository ,Trick $trick, ImageRepository $imageRepository, $id, VideoRepository $videoRepository)
    {
        $form = $this->createForm(TrickType::class, $trick);
        $images = $imageRepository->findBy(array('trick'=>$id));
        $video = $videoRepository->findBy(array('trick'=>$id));
        $name = $trickRepository->findBy(array('id'=>$id));
        $form->handleRequest($request);
        $images2 = $form->get('images')->getData();
        if ($form->isSubmitted() && $form->isValid())
        {

            foreach($images2 as $image)
            {
                $fichier = md5(uniqid()).'.'.$image->guessExtension();
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );
                $img = new Image();
                $img->setName($fichier);
                $trick->addImage($img);
            }
            try{
                $manager -> persist ( $trick );
                $manager -> flush ();
                $this->addFlash('updateok', 'Your trick is now updated ! Thank\'s');
            } catch (\Exception $e) {
                $this->addFlash('updatebad', 'Oups...something goes wrong, please try again');
                return $this->redirectToRoute('home');
            }
            return $this->redirectToRoute('home');
        }
        $featuredImage = $trickRepository->showFeaturedImage($id);
        return $this->render("trick/edit.html.twig", array('featuredImage'=>$featuredImage,'images'=>$images,'name'=>$name,'videos'=>$video,'id'=>$id,'form'=>$form->createView()));
    }


}


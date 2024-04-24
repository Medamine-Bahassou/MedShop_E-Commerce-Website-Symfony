<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{

    private $userRepository ;
    private $entityManager ; 

    public function __construct(
        UserRepository $userRepository,
        ManagerRegistry $doctrine
        )
    {
        $this->userRepository = $userRepository ; 
        $this->entityManager = $doctrine->getManager() ; 
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $this->redirectToRoute('home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('');
        }
        return $this->render('profile/profile.html.twig',[
            'user' => $this->getUser(),
        ]);
    }



    #[Route('/profile/edit/{id}', name: 'profile_edit')]
    public function editProduct(Request $request, UserPasswordHasherInterface $userPasswordHasher, $id): Response
    {
        $user = new User();
        $user = $this->userRepository->find($id);
        $form = $this->createForm(ProfileType::class , $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Your profile was updated'
            );
            return $this->redirectToRoute('profile');
        }

        return $this->renderForm('profile/edit.html.twig', [
            'profileform' => $form,
            'id' => $id ,
        ]);
    }

    #[Route('/profile/delete/{id}', name: 'profile_delete')]
    public function deleteProduct($id): Response
    {
        $profile = new User();
        $profile = $this->userRepository->find($id) ; 
        $this->entityManager->remove($profile);
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            'Your product was removed'
        );

        return $this->redirectToRoute('home');
    }

}

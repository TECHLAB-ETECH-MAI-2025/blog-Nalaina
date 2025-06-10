<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Form\AdminUserForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index( UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $userCount = $userRepository->count([]);
        $verifiedCount = $userRepository->count(['isVerified' => true]);
        $adminCount = $userRepository->countAdmins();
        
        return $this->render('admin/index.html.twig', [
            'userCount' => $userCount,
            'verifiedCount' => $verifiedCount,
            'adminCount' => $adminCount,
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository):Response{
        $usesrs = $userRepository->findAll();
        return $this->render('admin/users.html.twig', [
            'users' => $usesrs,
        ]);
    }

    #[Route('/admin/users/new', name: 'app_admin_users_new')]
    public function newUser(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(AdminUserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  
            // encoder les mots de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setIsVerified(true);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès !');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }   

    #[Route('/admin/users/{id}/edit', name: 'app_admin_users_edit')]
    public function editUser(User $user, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminUserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encoder les mots de passe
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès !');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function deleteUser(User $user, Request $request, EntityManagerInterface $entityManager) : Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_admin_users');
            }
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de l\'utilisateur.');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}

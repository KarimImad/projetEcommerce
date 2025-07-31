<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
    #[Route('admin/user', name: 'app_user')]
    public function index(UserRepository $user_repository): Response
    {
        
        return $this->render('user/allUsers.html.twig', [
            'controller_name' => 'UserController',
            'users' => $user_repository -> findAll(),
        ]);
    }

    #[Route('admin/user/update/{id}', name: 'app_user_change_role')]
    #[IsGranted("ROLE_ADMIN")]
    public function updateRole(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles(['ROLE_EDITOR','ROLE_USER']);
        $entityManager->flush();

        $this->addFlash('success', 'The user\'s role have been updated to Editor !');


        return $this->redirectToRoute('app_user');
    }

    #[Route('admin/user/delete/{id}', name: 'app_user_delete_role')]
    #[IsGranted("ROLE_ADMIN")]
    public function deleteRole(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('success', 'The user\'s role have been updated to Editor !');


        return $this->redirectToRoute('app_user');
    }

    #[Route('admin/user/{id}/remove', name: 'app_user_remove')]
    #[IsGranted("ROLE_ADMIN")]
    public function removeUser(EntityManagerInterface $entityManager, UserRepository $userRepository,$id): Response
    {
        $user=$userRepository->find($id);
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', 'The user\'s deleted!');


        return $this->redirectToRoute('app_user');
    }

   

}

<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function updateRole(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles(['ROLE_EDITOR','ROLE_USER']);
        $entityManager->flush();

        return $this->redirectToRoute('app_user');
    }

}

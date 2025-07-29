<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function index(CategoryRepository $category_repository): Response
    {
        $categories = $category_repository -> findAll(); 
        return $this->render('category/allCategories.html.twig', [
            'controller_name' => 'CategoryController',
            'categories' => $categories
        ]);
    }

     #[Route('/category/new', name: 'app_category_new')]
    public function addCategory(EntityManagerInterface $entityManager, Request $request): Response
    {   $category = new Category();

        $form = $this->createForm(CategoryFormType::class, $category);
        $form -> handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('succes','Votre categorie a bien été créée');

            return $this->redirectToRoute('app_category');
        }

        return $this->render('category/newCategory.html.twig', [
            'form' => $form->createView()
           
        ]);
    }

     #[Route('/update/{id}', name :'update')]
    public function update(Request $request, $id, CategoryRepository $CategoryRepo, EntityManagerInterface $entityManager): Response
    {
        $data = $entityManager->getRepository(Category::class)->find($id);
        $form = $this->createForm(CategoryFormType::class, $data, [
    
        ]);
        $form->handleRequest($request);
        if ( $form->isSubmitted()&& $form->isValid()){
            $entityManager->persist($data);
            $entityManager->flush();

            return $this->redirectToRoute('app_category');
            
        }
        $datas = $CategoryRepo->findAll();
        return $this->render('category/updateCategory.html.twig', [
        'form' => $form->createView(),
        'datas'=>$datas,
        ]);
    }
    
     #[Route('/delete/{id}', name :'delete')]
    public function delete($id, EntityManagerInterface $entityManager): Response
    {
        $data = $entityManager->getRepository(Category::class)->find($id);
        $entityManager->remove($data);
        $entityManager->flush();

        return $this->redirectToRoute('app_category');

    }

}

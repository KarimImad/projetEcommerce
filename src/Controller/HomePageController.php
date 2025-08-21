<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\SubCategory;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page', methods:['GET'])]
    public function index(ProductRepository $productRepo, CategoryRepository $categoryRepository, Request $request, PaginatorInterface $paginator): Response
    {   
        // $search= $productRepo->SearchEngine('mma');
        // dd($search);

        $data = $productRepo->findby([],['id'=>"DESC"]);
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page',1),
            8
        );


        return $this->render('home_page/index.html.twig', [
            'controller_name' => 'HomePageController',
            'products' => $products,
            'categories'=>$categoryRepository ->findAll()
        ]);
    }

     #[Route('/product/{id}/show', name: 'app_home_product_show', methods:['GET'])]
    public function showProduct(Product $product, ProductRepository $productRepository,CategoryRepository $categorieRepository): Response
    {   
        $lastProductsAdd = $productRepository->findBy([],['id'=>'DESC'],5);

        return $this->render('home_page/show.html.twig', [
            
            'product' => $product,
            'products' =>$lastProductsAdd,
            'categories'=>$categorieRepository->findAll()
        ]);
    }

     #[Route('/product/subcategory/{id}/filter', name: 'app_home_product_filter', methods:['GET'])]
    public function filter($id,SubCategoryRepository $subCategoryRepository, CategoryRepository $categoryRepository): Response

    {   //on recupere la sous categorie correspondante à l'id passé en paramètre
        // on accede aux products de cette sous catégorie
        $product = $subCategoryRepository->find($id)->getProducts();
        //on récupère la sous catégorie complète (objet)
        $subCategory = $subCategoryRepository->find($id);

        return $this->render('home_page/filter.html.twig', [
        'products'=>$product,// liste des produits liés à la sous catégorie
        'subCategory'=>$subCategory,//l'objet sous catégorie qui correspond avec l'id
        'categories'=>$categoryRepository->findAll()//la liste de toutes les catégories vie la repo
            
            
        ]);
    }
}

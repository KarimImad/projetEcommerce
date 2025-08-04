<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use App\Form\ProductType;
use App\Entity\AddProductHistory;
use App\Form\AddProductHistoryType;
use App\Repository\AddProductHistoryRepository;
use PhpParser\Node\Stmt\TryCatch;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/editor/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

#region ADD

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData(); // on recupère le fichier de l'image qui sera upload//

            if($image){ //si l'image a bien été envoyée 
                $originalName = pathinfo($image->getCLientOriginalName(), PATHINFO_FILENAME);//on récupère le nom d'origine sans les extensions (jpeg etc)
                $safeImageName = $slugger->slug($originalName);//on va "slugger" donc remplacer tous les accents,espaces etc par un " - "
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();//ajoute un id unique et donc l'extension 

                try { //ça va déplacer le fichier, ici l'image, dans le dossier que j'aurai défini dans le paramètre imagedirectory,
                    $image->move
                        ($this->getParameter('image_directory'),
                        $newFileImageName);
                        
                } catch (FileException $exception) {
                    //gestion d'un message erreur si besoin 
                }     
                    $product->setImage($newFileImageName); // on sauvegarde le nom du fichier dans son entité
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $stockHistory = new AddProductHistory();
            $stockHistory->setQuantity($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new DateTimeImmutable());
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('succes','Votre produit a été ajouté');


            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

#endregion

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/product/{id}/', name: 'app_product_stock_add', methods: ['GET','POST'])]
    public function stockAdd($id,Request $request, EntityManagerInterface $entityManager,ProductRepository $productRepo): Response
    {
      $stockAdd = new AddProductHistory();
      $form=$this->createForm(AddProductHistoryType::class, $stockAdd);
      $form->handleRequest($request);
      $product=$productRepo->find($id);
    
      if ($form->isSubmitted() && $form->isValid()) {

        if($stockAdd->getQuantity()>0){
            $newQuantity = $product->getStock() + $stockAdd->getQuantity();
            $product->setStock($newQuantity);

            $stockAdd->setCreatedAt(new DateTimeImmutable());
            $stockAdd->setProduct($product);
            $entityManager->persist($stockAdd);
            $entityManager->flush();

            $this->addFlash('succes',"Le stock du produit a été modifié");
            return $this->redirectToRoute('app_product_index');
        }
      
     }

     return $this->render('product/addStock.html.twig',
        ['form'=> $form->createView(),
        'product'=>$product,
        ]

      );

    }

     #[Route('/add/product/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function showHistoryProductStocks($id,ProductRepository $productRepository,AddProductHistoryRepository $addProductHistoryRepository): Response
    {
        $Product= $productRepository->find($id);
        $productAddHistory = $addProductHistoryRepository->findBy(['product'=>$Product],['id'=>'DESC']);

        return $this->render('product/addedHistoryStockshow.html.twig', [
            "productsAdded"=>$productAddHistory
        ]);

    }

}


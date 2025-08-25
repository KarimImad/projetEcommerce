<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchEngineController extends AbstractController
{
    #[Route('/search/engine', name: 'app_search_engine')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {   //vérifie si la requête est de type GET
        if ($request->isMethod('POST')){
            $word= $request->get("word");
            //Appelle la méthode searchengine du repository
            $results = $productRepository->searchEngine($word);
        }
        //Rendu de la vue search_engine/twig 
        return $this->render('search_engine/index.html.twig', [
            'products' => $results,
            'word' => $word,
        ]);
    }
}

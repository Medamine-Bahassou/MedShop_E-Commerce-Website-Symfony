<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Name;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem ;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{

    private $productRepository ;
    private $entityManager ; 

    public function __construct(
        ProductRepository $productRepository,
        ManagerRegistry $doctrine
        )
    {
        $this->productRepository = $productRepository ; 
        $this->entityManager = $doctrine->getManager() ; 
    }

    #[Route('/product', name: 'product_list')]
    public function index(): Response
    {
        $products = $this->productRepository->findAll();
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/store/product', name: 'product_store')]
    public function store(Request $request ): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class , $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $product = $form->getData();
            if($request->files->get('product')['image'])
            {
                $image = $request->files->get('product')['image'] ;
                $image_name = time().'_'.$image->getClientOriginalName();
                $image->move($this->getParameter('image_directory'),$image_name);
                $product->setImage($image_name);
            }
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Your product was saved'
            );
            return $this->redirectToRoute('product_list');
        }

        return $this->renderForm('product/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/product/detail/{id}', name: 'product_show')]
    public function showProduct($id): Response
    {
        $product = new Product();
        $product = $this->productRepository->find($id) ;    
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/product/edit/{id}', name: 'product_edit')]
    public function editProduct(Request $request, $id): Response
    {
        $product = new Product() ; 
        $product = $this->productRepository->find($id);
        $form = $this->createForm(ProductType::class , $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $product = $form->getData();
            if($request->files->get('product')['image'])
            {
                $image = $request->files->get('product')['image'] ;
                $image_name = time().'_'.$image->getClientOriginalName();
                $image->move($this->getParameter('image_directory'),$image_name);
                $product->setImage($image_name);
            }
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Your product was updated'
            );
            return $this->redirectToRoute('product_list');
        }

        return $this->renderForm('product/edit.html.twig', [
            'form' => $form,
            'id' => $id ,
        ]);
    }
    
    #[Route('/product/delete/{id}', name: 'product_delete')]
    public function deleteProduct($id): Response
    {
        $product = new Product();
        $product = $this->productRepository->find($id) ; 
        $filesystem = new Filesystem();
        $imagePath = './uploads/'.$product->getImage();
        if($filesystem->exists($imagePath)){
            $filesystem->remove($imagePath);
        }
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            'Your product was removed'
        );

        return $this->redirectToRoute('product_list');
    }
    

    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $search = $request->request->get('search'); // Récupérer la valeur de recherche depuis la requête POST

        // Construire la requête pour récupérer les noms de produits correspondant à la recherche
        $queryBuilder = $this->productRepository->createQueryBuilder('p');
        $queryBuilder->select('p.name')
            ->where($queryBuilder->expr()->like('CONCAT(\' % \', p.name, \' % \')', ':searchTerm'))
            ->setParameter('searchTerm', '% ' . $search . ' %');

        // Exécuter la requête pour obtenir les noms des produits correspondant à la recherche
        $productNames = $queryBuilder->getQuery()->getResult();

        // Récupérer tous les produits
        $allProducts = $this->productRepository->findAll();

        // Initialiser le tableau pour stocker les produits trouvés
        $foundProducts = [];

        // Itérer sur tous les produits pour trouver ceux correspondant aux noms récupérés
        foreach ($allProducts as $product) {
            if (in_array($product->getName(), array_column($productNames, 'name'))) {
                $foundProducts[] = $product;
            }
        }

        // Rendre la vue avec les produits trouvés
        return $this->render('product/search.html.twig', [
            'products' => $foundProducts,
        ]);
    }
    


    

}

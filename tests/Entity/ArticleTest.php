<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use App\Entity\Article;
use App\Entity\Categorie;

final class ArticleTest extends TestCase
{
    public function testSetTitre(): void
    {
        $article = new Article();
        $article->setTitre('Mon Titre');
        $this->assertEquals('Mon Titre', $article->getTitre());
    }
}
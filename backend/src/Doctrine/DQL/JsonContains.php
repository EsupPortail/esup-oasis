<?php /** @noinspection PhpMissingFieldTypeInspection */

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class JsonContains extends FunctionNode
{
    /** @noinspection PhpMissingFieldTypeInspection */
    private $string1;
    private $string2;

    public function getSql(SqlWalker $sqlWalker): string
    {
        //$str = $this->string->dispatch($sqlWalker) . "::jsonb \? " . $this->string->dispatch($sqlWalker);
        return 'jsonb_exists(' . $this->string1->dispatch($sqlWalker) . ', ' . $this->string2->dispatch($sqlWalker) . ')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->string1 = $parser->StringPrimary();

        $parser->match(TokenType::T_COMMA);
        $this->string2 = $parser->StringPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

}
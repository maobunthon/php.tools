<?php
final class ReindentEqual extends FormatterPass {
    /**
     * @param $source
     * @param $foundTokens
     */
    public function candidate($source, $foundTokens) {
        return true;
    }

    /**
     * @param $source
     * @return mixed
     */
    public function format($source) {
        $this->tkns = token_get_all($source);
        $this->code = '';

        for ($index = sizeof($this->tkns) - 1; 0 <= $index; --$index) {
            $token = $this->tkns[$index];
            list($id) = $this->getToken($token);
            $this->ptr = $index;

            if (ST_SEMI_COLON == $id) {
                --$index;
                $this->scanUntilEqual($index);
            }
        }

        return $this->render($this->tkns);
    }

    /**
     * @param $index
     * @return null
     */
    private function scanUntilEqual($index) {
        $indentBack = [];
        for ($index; 0 <= $index; --$index) {
            $token = $this->tkns[$index];
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            switch ($id) {
            case ST_CURLY_CLOSE:
                $this->refWalkCurlyBlockReverse($this->tkns, $index);
                break;

            case ST_PARENTHESES_CLOSE:
                $count = 0;
                $indent = '';
                $ptr = $this->ptr;
                for (; $index >= 0; --$index) {
                    $this->ptr = $index;
                    $id = $this->tkns[$index][0];
                    $currText = isset($this->tkns[$index][1]) ? $this->tkns[$index][1] : $id;

                    $hasLnBefore = $this->hasLnBefore();
                    if ($hasLnBefore) {
                        $this->tkns[$index] = [$id, $currText];
                        $indentBack[$index] = true;
                    }
                    if (ST_PARENTHESES_OPEN == $id) {
                        --$count;
                    }
                    if (ST_PARENTHESES_CLOSE == $id) {
                        ++$count;
                    }
                    if (0 == $count) {
                        break;
                    }

                }
                $this->ptr = $ptr;
                // $this->refWalkBlockReverse($this->tkns, $index, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
                break;

            case ST_BRACKET_CLOSE:
                $this->refWalkBlockReverse($this->tkns, $index, ST_BRACKET_OPEN, ST_BRACKET_CLOSE);
                break;

            case ST_QUOTE:
            case T_CONSTANT_ENCAPSED_STRING:
            case ST_CONCAT:
            case T_STRING:
            case T_VARIABLE:
            case ST_TIMES:
            case ST_DIVIDE:
            case ST_PLUS:
            case ST_MINUS:
            case T_POW:
                break;

            case T_WHITESPACE:
                if (
                    $this->hasLn($text)
                    &&
                    !
                    (
                        $this->rightUsefulTokenIs([ST_SEMI_COLON])
                        ||
                        $this->leftUsefulTokenIs([
                            ST_BRACKET_OPEN,
                            ST_COLON,
                            ST_CURLY_CLOSE,
                            ST_CURLY_OPEN,
                            ST_PARENTHESES_OPEN,
                            ST_SEMI_COLON,
                            T_END_HEREDOC,
                            T_OBJECT_OPERATOR,
                            T_OPEN_TAG,
                        ])
                        ||
                        $this->leftTokenIs([
                            T_COMMENT,
                            T_DOC_COMMENT,
                        ])
                    )
                ) {
                    $text .= $this->indentChar;
                    $this->tkns[$index] = [$id, $text];
                }
                break;

            default:
                return;
            }
        }
    }
}

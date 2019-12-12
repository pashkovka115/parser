<?php

header( 'Content-Type: text/html; charset=UTF-8' );


class Parser {
    private $cursor = 0;
    private $html = '';
    private $length_html = 0;


    public function __construct( $html ) {
        if ( empty( $html ) ) {
            throw new Exception( 'Не корректный HTML!' );
        }
        mb_internal_encoding( 'UTF-8' );
        mb_http_output( 'UTF-8' );
        mb_http_input( 'UTF-8' );
        mb_regex_encoding( 'UTF-8' );

        $patterns = [
          '/(\s{2,})|(\r\n)|(\n)/',
          '/(\>\s+\<)/im'
        ];
        $replace  = [
          ' ',
          '><'
        ];
//		$this->html = preg_replace( $patterns, $replace, $html );
        $this->html        = preg_replace( $patterns, $replace, mb_convert_encoding( $html, 'UTF-8' ) );
        $this->length_html = mb_strlen( $this->html );

//		file_put_contents(__DIR__.'/home.min.html', $this->html);
    }


    /**
     * @param string $open_tag - С "<tag_name" до "универсальности строки"
     * Чем универсальней входная строка, тем точнее выборка
     * Прямо из HTML копируем открывающий тэг в длинною до нужной универсальности строки.
     *
     * Возвращает первый найденный HTML элемент со всеми вложенностями в виде строки
     * Курсор остаётся в конце выбранного элемента
     *
     * Не работает с одиночными тэгами, для этого есть getDOMDocument()
     *
     * @return string
     */
    public function getString( $open_tag ) {
        // стартовая позиция искомой строки
        $start = mb_stripos( $this->html, $open_tag, $this->cursor );
        if ( $start === FALSE ) {
            return FALSE;
        }
        // запоминаем начальную позицию искомой строки
        $this->cursor = $start;
        // копируем искомую строку от стартовой позиции до конца файла
        $html = mb_substr( $this->html, $start );

        // читаем название открывающегося тэга
        preg_match( '/\<([?!a-z])[^\s\>]*/', $html, $matches );

        // формируем открывающий тэг (без закрывающей скобки)
        $start_tag = $matches[0];

        // формируем закрывающийся тэг
        if ( $start_tag[ mb_strlen( $start_tag ) - 1 ] != '>' ) {
            $end_tag = str_replace( '<', '</', $start_tag ) . '>';
        } else {
            $end_tag = str_replace( '<', '</', $start_tag );
        }

        // вложенность искомого тэга
        $depth      = 0;
        $i          = 0;
        $is_present = FALSE;
        $max_len    = mb_strlen( $open_tag ) + 3;

        while ( TRUE ) {
            // сдвигаем зачитываемую строку по одному символу
            $next_html = mb_substr( $html, $i, $max_len );

            if ( preg_match( '#^' . $start_tag . '#', $next_html ) ) {
                $depth ++;
                if ( $depth > 1 ) {
                    $is_present = TRUE;
                }

            }
            if ( preg_match( '#^' . $end_tag . '#', $next_html ) ) {
                $depth --;
            }


            // если была вложенность и вышли на нулевой уровень
            if ( $is_present and $depth == 0 ) {
                $this->cursor += mb_strlen( $end_tag );
                break;
            }
            // если нет вложенности
            if ( preg_match( '#^' . $end_tag . '#', $next_html ) and $depth == 0 ) {
                $this->cursor += mb_strlen( $end_tag );
                break;
            }

            // если достигнут конец строки (файла)
            // аварийный выход из цикла
            if ( $this->cursor >= ( $this->length_html - mb_strlen( $open_tag ) + 2 ) ) {
                return;
            }

            $this->cursor ++;
            $i ++;
        }

        return mb_substr( $html, 0, $this->cursor - $start );
    }


    /**
     * @param $open_tag
     *
     * @return array
     * Возвращает массив всех найденных HTML элементов со всеми вложенностями
     */
    public function getStringAll( $open_tag ) {
        $arr = [];
        while (TRUE){
            $t = $this->getString( $open_tag );
            if ($t !== FALSE) {
                $arr[] = $t ;
            }else{
                break;
            }
        }
        return $arr;
    }

    /**
     * @param $open_tag
     *
     * @return array
     * Возвращает массив всех найденных HTML элементов со всеми вложенностями
     * каждый элемент объект DOMDocument()
     */
    public function getDOMDocumentAll( $open_tag ) {
        $arr = [];
        while (TRUE){
            $t = $this->getDOMDocument( $open_tag );
            if ($t !== FALSE) {
                $arr[] = $t ;
            }else{
                break;
            }
        }
        return $arr;
    }


    /**
     * @param $open_tag
     *
     * @return bool|\DOMDocument
     * Тоже самое что и getString(), только возвращает в виде объекта DOMDocument()
     * ниже BODY
     */
    public function getDOMDocument( $open_tag ) {
        $str = $this->getString( $open_tag );
        if ($str === FALSE) {
            return FALSE;
        }
        $doc = new DOMDocument();
        $doc->loadHTML( mb_convert_encoding( $str, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL );

        return $doc->firstChild->firstChild->firstChild;
    }

    /**
     * @param $string
     * @return bool
     * Передвигаем курсор в конец первого вхождения подстроки
     */
    public function next($string) {
        // стартовая позиция искомой строки
        $start = mb_stripos( $this->html, $string, $this->cursor );
        if ( $start === FALSE ) {
            return FALSE;
        }
        // Передвигаем курсор в конец первого вхождения строки
        $this->cursor = $start + mb_strlen($string);
        return TRUE;
    }

    /**
     * @param int $count
     * @param $string
     * @return bool
     * Подымает курсор на $count символов,
     * а затем передвигает его вниз до первого найденного вхождения $string
     */
    public function prev(int $count, $string) {
        if ($count > $this->cursor){
            $this->cursor = 0;
        }else{
            $this->cursor = $this->cursor - $count;
        }
        $start = mb_stripos( $this->html, $string, $this->cursor );
        if ( $start === FALSE ) {
            return FALSE;
        }
        $this->cursor = $start;
        return TRUE;
    }

    /**
     * @return string
     * В целях отладки посмотреть где находится курсор
     * или
     * передать например в getString() для парсинга с текущего положения курсора
     */
    public function getViewCurcor() {
        return mb_substr( $this->html, $this->cursor, 200 );
    }

    // TODO:
    public function replaceHtml( $search, $replace ) {
    }


    public function getHtml() {
        return $this->html;
    }
}

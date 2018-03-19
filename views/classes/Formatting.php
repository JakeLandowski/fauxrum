<?php

abstract class Formatting
{
    public static function renderQuote($content)
    {
        $authorChunks  = preg_split('/\[author\](.*)\[\/author\]/i', trim($content), -1,  PREG_SPLIT_DELIM_CAPTURE);

        $authorContent  = isset($authorChunks[0]) ? $authorChunks[0] : '';
        $authorContent .= isset($authorChunks[1]) ? '<footer class="blockquote-footer">' . $authorChunks[1] . '</footer>' : '';
        $authorContent .= isset($authorChunks[2]) ? $authorChunks[2] : '';
        
        
        
        $quoteChunks = preg_split('/\[quote\](.*)\[\/quote\]/i', trim($authorContent), -1,  PREG_SPLIT_DELIM_CAPTURE);
        $quotedContent  = isset($quoteChunks[0]) ? $quoteChunks[0] : '';
        $quotedContent .= isset($quoteChunks[1]) ? '<blockquote class="blockquote font-italic text-muted">' . $quoteChunks[1] . '</blockquote>': ''; 
        $quotedContent .= isset($quoteChunks[2]) ? $quoteChunks[2] : '';
        
        
        echo '0 ' . htmlspecialchars($quoteChunks[0]);
        echo '<br>';
        echo '1 ' . htmlspecialchars($quoteChunks[1]);
        echo '<br>';
        echo '2 ' . htmlspecialchars($quoteChunks[2]);
        echo '<br>';
        echo '<br>';

        return $quotedContent;
    }

    public static function stripQuoteTags($content)
    {
        $newContent = preg_replace('/\[quote\].*\[\/quote\]|\[quote\]|\[\/quote\]/', '', $content);
        $newContent = preg_replace('/\[author\].*\[\/author\]|\[author\]|\[\/author\]/', '', $newContent);
        return $newContent;
    }

    public static function addTags($content, $author)
    {
        return "[quote]${content}[author]${author}[/author][/quote]";
    }
}
<?php
/**
 *  Provides methods for formatting text in templates
 *  or in map parsing, ex: striping or adding custom quote tags.  
 */

/**
 *  Provides methods for formatting text in templates
 *  or in map parsing, ex: striping or adding custom quote tags.
 *  
 *  @author Jacob Landowski
 */
abstract class Formatting
{
    /**
     *  Converts custom quote/author tags into html equivalent content.
     * 
     *  @param string $content the content to convert  
     */
    public static function renderQuote($content)
    {
        $authorChunks  = preg_split('/\[author\](\s*.*\s*)\[\/author\]/i', trim($content), -1,  PREG_SPLIT_DELIM_CAPTURE);

        $authorContent  = isset($authorChunks[0]) ? $authorChunks[0] : '';
        $authorContent .= isset($authorChunks[1]) ? '<footer class="blockquote-footer">' . $authorChunks[1] . '</footer>' : '';
        $authorContent .= isset($authorChunks[2]) ? $authorChunks[2] : '';
        
        $quoteChunks = preg_split('/\[quote\](\s*.*\s*)\[\/quote\]/i', trim($authorContent), -1,  PREG_SPLIT_DELIM_CAPTURE);
        $quotedContent  = isset($quoteChunks[0]) ? $quoteChunks[0] : '';
        $quotedContent .= isset($quoteChunks[1]) ? '<blockquote class="blockquote font-italic text-muted">' . $quoteChunks[1] . '</blockquote>': ''; 
        $quotedContent .= isset($quoteChunks[2]) ? $quoteChunks[2] : '';

        return $quotedContent;
    }

    /**
     *  Strips quote/author tags from content.
     * 
     *  @param string $content the content to convert  
     */
    public static function stripQuoteTags($content)
    {
        $newContent = preg_replace('/\[quote\].*\[\/quote\]|\[quote\]|\[\/quote\]/', '', $content);
        $newContent = preg_replace('/\[author\].*\[\/author\]|\[author\]|\[\/author\]/', '', $newContent);
        return $newContent;
    }

    /**
     *  Adds quote/author tags to content.
     * 
     *  @param string $content the content to convert  
     */
    public static function addTags($content, $author)
    {
        return '[quote]' . trim($content) . '[author]' . trim($author) . '[/author][/quote]';
    }
}
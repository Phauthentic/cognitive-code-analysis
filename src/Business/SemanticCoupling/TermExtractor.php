<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling;

/**
 * Extracts and normalizes identifier names from code.
 */
class TermExtractor
{
    /**
     * Common technical terms to filter out (can be configured).
     */
    private array $stopWords = [
        'data', 'temp', 'tmp', 'id', 'value', 'item', 'element', 'object', 'array',
        'string', 'int', 'bool', 'float', 'var', 'param', 'arg', 'result', 'return',
        'response', 'request', 'config', 'option', 'flag', 'status', 'state',
        'count', 'size', 'length', 'index', 'key', 'name', 'type', 'class',
        'method', 'function', 'callback', 'handler', 'listener', 'event',
        'error', 'exception', 'message', 'text', 'content', 'body', 'header',
        'file', 'path', 'url', 'link', 'ref', 'refs', 'ptr', 'pointer'
    ];

    public function __construct(array $customStopWords = [])
    {
        $this->stopWords = array_merge($this->stopWords, $customStopWords);
        $this->stopWords = array_unique($this->stopWords);
    }

    /**
     * Extract terms from a file's identifier names.
     *
     * @param array<string> $identifiers Raw identifier names from AST
     * @return array<string, int> Term frequency map
     */
    public function extractTermsFromIdentifiers(array $identifiers): array
    {
        $terms = [];
        
        foreach ($identifiers as $identifier) {
            $normalizedTerms = $this->normalizeIdentifier($identifier);
            
            foreach ($normalizedTerms as $term) {
                if ($this->isValidTerm($term)) {
                    $terms[$term] = ($terms[$term] ?? 0) + 1;
                }
            }
        }

        return $terms;
    }

    /**
     * Normalize an identifier by splitting camelCase, snake_case, etc.
     *
     * @param string $identifier
     * @return array<string>
     */
    public function normalizeIdentifier(string $identifier): array
    {
        // Remove common prefixes/suffixes
        $identifier = $this->removeCommonPrefixes($identifier);
        
        // Split camelCase and PascalCase
        $terms = $this->splitCamelCase($identifier);
        
        // Split snake_case and kebab-case
        $allTerms = [];
        foreach ($terms as $term) {
            $allTerms = array_merge($allTerms, $this->splitSnakeCase($term));
        }
        
        // Lowercase all terms
        $allTerms = array_map('strtolower', $allTerms);
        
        // Remove empty terms
        return array_filter($allTerms, fn($term) => !empty($term));
    }

    /**
     * Check if a term is valid (not a stop word, has minimum length).
     */
    private function isValidTerm(string $term): bool
    {
        // Minimum length check
        if (strlen($term) < 2) {
            return false;
        }
        
        // Stop word check
        if (in_array($term, $this->stopWords, true)) {
            return false;
        }
        
        // Only alphabetic characters (no numbers or special chars)
        return ctype_alpha($term);
    }

    /**
     * Remove common prefixes from identifiers.
     */
    private function removeCommonPrefixes(string $identifier): string
    {
        $prefixes = ['get', 'set', 'is', 'has', 'can', 'should', 'will', 'do', 'create', 'build', 'make'];
        
        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($identifier), $prefix) && strlen($identifier) > strlen($prefix)) {
                $identifier = substr($identifier, strlen($prefix));
                break;
            }
        }
        
        return $identifier;
    }

    /**
     * Split camelCase and PascalCase identifiers.
     */
    private function splitCamelCase(string $identifier): array
    {
        // Insert space before uppercase letters that follow lowercase letters
        $split = preg_replace('/([a-z])([A-Z])/', '$1 $2', $identifier);
        
        return explode(' ', $split);
    }

    /**
     * Split snake_case and kebab-case identifiers.
     */
    private function splitSnakeCase(string $identifier): array
    {
        return preg_split('/[_-]+/', $identifier);
    }

    /**
     * Get all stop words.
     */
    public function getStopWords(): array
    {
        return $this->stopWords;
    }

    /**
     * Add custom stop words.
     */
    public function addStopWords(array $words): void
    {
        $this->stopWords = array_merge($this->stopWords, $words);
        $this->stopWords = array_unique($this->stopWords);
    }

    /**
     * Set custom stop words (replaces existing).
     */
    public function setStopWords(array $words): void
    {
        $this->stopWords = $words;
    }
}

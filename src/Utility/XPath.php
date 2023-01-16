<?php

namespace App\Utility;

class XPath
{
    protected ?string $class;
    protected ?string $prefix;
    protected ?string $suffix;

    /** @var array<string> */
    protected array $tags;

    protected ?string $text;
    protected bool $textStartsWith;

    public function __construct()
    {
        $this->class = null;
        $this->prefix = null;
        $this->suffix = null;
        $this->tags = [];
        $this->text = null;
        $this->textStartsWith = false;
    }

    public static function create(): self {
        return new XPath();
    }

    public function withClass(string $class): self {
        $this->class = $class;
        return $this;
    }

    public function withPrefix(string $prefix): self {
        $this->prefix = $prefix;
        return $this;
    }

    public function withSuffix(string $suffix): self {
        $this->suffix = $suffix;
        return $this;
    }

    public function withTag(string $tag): self {
        $this->tags[] = $tag;
        return $this;
    }

    public function withTags(string $tags): self {
        $this->tags = array_merge($this->tags, explode(',', $tags));
        return $this;
    }

    public function withText(string $text): self {
        $this->text = $text;
        return $this;
    }

    public function withTextStartsWith(string $text): self {
        $this->withText($text);
        $this->textStartsWith = true;
        return $this;
    }

    public function __toString(): string
    {
        $predicates = [];

        if ($this->text) {
            if ($this->textStartsWith) {
                $predicates[] = "starts-with(normalize-space(text()), '{$this->text}')";
            } else {
                $predicates[] = "normalize-space(text()) = '{$this->text}'";
            }
        }

        if ($this->class) {
            $predicates[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$this->class} ')";
        }

        $predicates = join(' and ', $predicates);

        $xPath = ($this->prefix ?? '').'//';
        $numTags = count($this->tags);
        if ($numTags === 1) {
            $xPath .= reset($this->tags);
        } else {
            $xPath .= '*';

            $selfTags = array_map(fn(string $tag) => "self::$tag", $this->tags);
            $tagPredicates = join(' or ', $selfTags);
            $predicates = "({$tagPredicates}) and ({$predicates})";
        }

        return $xPath.($predicates ? "[{$predicates}]" : '').($this->suffix ?? '');
    }
}
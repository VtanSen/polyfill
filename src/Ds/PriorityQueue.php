<?php
namespace Ds;

use UnderflowException;

/**
 *
 */
final class PriorityNode
{
    public $value;
    public $priority;
    public $stamp;

    public function __construct($value, int $priority, int $stamp)
    {
        $this->value    = $value;
        $this->priority = $priority;
        $this->stamp    = $stamp;
    }
}

/**
 *
 */
final class PriorityQueue implements \IteratorAggregate, Collection
{
    use Traits\Collection;
    use Traits\SquaredCapacity;

    /**
     * @var int
     */
    const MIN_CAPACITY = 8;

    /**
     * @var array
     */
    private $heap;

    /**
     * @var int
     */
    private $stamp;

    /**
     * Initializes a new priority queue.
     */
    public function __construct()
    {
        $this->heap = [];
        $this->stamp = 0;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->heap = [];
        $this->stamp = 0;
        $this->capacity = self::MIN_CAPACITY;
    }

    /**
     * @inheritDoc
     */
    public function copy()
    {
        $copy = new PriorityQueue();
        $copy->heap = $this->heap;
        $copy->capacity = $this->capacity;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->heap);
    }

    /**
     * Returns the value with the highest priority in the priority queue.
     *
     * @return mixed
     *
     * @throw UnderflowException
     */
    public function peek()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->heap[0]->value;
    }

    /**
     *
     */
    private function left(int $index): int
    {
        return ($index * 2) + 1;
    }

    /**
     *
     */
    private function right(int $index): int
    {
        return ($index * 2) + 2;
    }

    /**
     *
     */
    private function parent(int $index): int
    {
        return ($index - 1) / 2;
    }

    private function compare(int $a, int $b)
    {
        $x = $this->heap[$a];
        $y = $this->heap[$b];

        // Compare priority, using insertion stamp as fallback.
        return ($x->priority <=> $y->priority) ?: ($y->stamp <=> $x->stamp);
    }

    /**
     *
     */
    private function swap(int $a, int $b)
    {
        $temp = $this->heap[$a];
        $this->heap[$a] = $this->heap[$b];
        $this->heap[$b] = $temp;
    }

    private function getLargestLeaf(int $parent)
    {
        $left  = $this->left($parent);
        $right = $left + 1;

        if ($right < count($this->heap) && $this->compare($left, $right) < 0) {
            return $right;
        }

        return $left;
    }

    private function siftDown(int $node)
    {
        $last = floor(count($this->heap) / 2);

        for ($parent = $node; $parent < $last; $parent = $leaf) {

            // Determine the largest leaf to potentially swap with the parent.
            $leaf = $this->getLargestLeaf($parent);

            // Done if the parent is not greater than its largest leaf
            if ($this->compare($parent, $leaf) > 0) {
                break;
            }

            $this->swap($parent, $leaf);
        }
    }

    private function setRoot(PriorityNode $node)
    {
        $this->heap[0] = $node;
    }

    private function getRoot(): PriorityNode
    {
        return $this->heap[0];
    }

    /**
     * Returns and removes the value with the highest priority in the queue.
     *
     * @return mixed
     */
    public function pop()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        // Last leaf of the heap to become the new root.
        $leaf = array_pop($this->heap);

        if ( ! $this->heap) {
            return $leaf->value;
        }

        // Cache the current root value to return before replacing with next.
        $value = $this->getRoot()->value;

        // Replace the root, then sift down.
        $this->setRoot($leaf);
        $this->siftDown(0);
        $this->adjustCapacity();

        return $value;
    }

    /**
     *
     */
    private function siftUp(int $leaf)
    {
         for (; $leaf > 0; $leaf = $parent) {

            $parent = $this->parent($leaf);

            // Done when parent priority is greater.
            if ($this->compare($leaf, $parent) < 0) {
                break;
            }

            $this->swap($parent, $leaf);
        }
    }

    /**
     * Pushes a value into the queue, with a specified priority.
     *
     * @param mixed $value
     * @param int   $priority
     */
    public function push($value, int $priority)
    {
        $this->adjustCapacity();

        // Add new leaf, then sift up to maintain heap,
        $this->heap[] = new PriorityNode($value, $priority, $this->stamp++);
        $this->siftUp(count($this->heap) - 1);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $heap = $this->heap;
        $array = [];

        while ( ! $this->isEmpty()) {
            $array[] = $this->pop();
        }

        $this->heap = $heap;
        return $array;
    }

    /**
     *
     */
    public function getIterator()
    {
        while ( ! $this->isEmpty()) {
            yield $this->pop();
        }
    }
}

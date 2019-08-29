<?php
/*
    Condorcet PHP - Election manager and results calculator.
    Designed for the Condorcet method. Integrating a large number of algorithms extending Condorcet. Expandable for all types of voting systems.

    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

namespace CondorcetPHP\Condorcet;

use CondorcetPHP\Condorcet\ElectionProcess\VoteUtil;
use CondorcetPHP\Condorcet\Throwable\CondorcetException;

class Ranking implements \ArrayAccess, \Countable, \Iterator
{
    use CondorcetVersion;

    // Implement Iterator

        private $position = 1;

        public function rewind() : void {
            $this->position = 1;
        }

        public function current() : array {
            return $this->_ranking[$this->position];
        }

        public function key() : int {
            return $this->position;
        }

        public function next() : void {
            ++$this->position;
        }

        public function valid() : bool {
            return isset($this->_ranking[$this->position]);
        }

    // Implement ArrayAccess

        public function offsetSet ($offset, $value) : void {
            throw new CondorcetException (0,"Can't change Ranking by Array Access");
        }

        public function offsetExists ($offset) : bool {
            return isset($this->_ranking[$offset]);
        }

        public function offsetUnset ($offset) : void {
            throw new CondorcetException (0,"Can't change Ranking by Array Access");
        }

        public function offsetGet ($offset) {
            return $this->_ranking[$offset] ?? null;
        }

    // Implement Countable

        public function count () : int {
            return $this->getRanksCount();
        }


    protected $_ranking = [];
    protected $_CandidateCounter = 0;

    public function __construct ($ranking_input)
    {
        $this->setRanking($ranking_input);
    }

    public function getRankingAsArray () : array
    {
        return $this->_ranking;
    }

    public function setRanking ($ranking) : void
    {
        
        if ($ranking instanceof Ranking) :
            $this->_ranking = $ranking->getRankingAsArray();
            return;
        endif;

        if (is_string($ranking)) :
            $ranking = VoteUtil::convertVoteInput($ranking);
        endif;

        if ( !is_array($ranking)) :
            throw new CondorcetException(5);
        endif;

        $ranking = array_filter($ranking, function ($key) {
            return is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);

        ksort($ranking);
        
        $i = 1; $vote_r = [];
        foreach ($ranking as &$value) :
            if ( !is_array($value) ) :
                $vote_r[$i] = [$value];
            else :
                $vote_r[$i] = $value;
            endif;

            $i++;
        endforeach;

        $ranking = $vote_r;

        $this->_CandidateCounter = 0;
        $list_candidate = [];
        foreach ($ranking as &$line) :
            foreach ($line as &$Candidate) :
                if ( !($Candidate instanceof Candidate) ) :
                    $Candidate = new Candidate ($Candidate);
                    $Candidate->setProvisionalState(true);
                endif;

                $this->_CandidateCounter++;

            // Check Duplicate

                // Check objet reference AND check candidates name
                if (!in_array($Candidate, $list_candidate)) :
                    $list_candidate[] = $Candidate;
                else : 
                    throw new CondorcetException(5);
                endif;

            endforeach;
        endforeach;

        $this->_ranking = $ranking;
    }

    public function getCandidatesCount() : int
    {
        return $this->_CandidateCounter;
    }

    public function getRanksCount() : int
    {
        return count($this->_ranking);
    }
}

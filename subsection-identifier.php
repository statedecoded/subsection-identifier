<?php

/**
 * Subsection Identifier
 *
 * 
 * Requires the object $this->text, with each section stored as a child ($this->text->0,
 * $this->text->1, etc.) Returns a hierarchically structured, labelled object, named
 * $this->structured, with a prefix_hiearchy object (containing an entry for the structural
 * identifier of each generation in the structural ancestry, in order), a prefix entry (containing
 * the section's structural identifer), and a text entry, with the structural identifier stripped
 * off.
 * 
 * PHP version 5
 *
 * @author		Waldo Jaquith <waldo at jaquith.org>
 * @copyright		2013 Waldo Jaquith
 * @license		http://www.gnu.org/licenses/gpl.html GPL 3
 * @version		1.0
 * @link		http://www.statedecoded.com/
 * @since		1.0
 *
 */

class SubsectionIdentifier
{

	function parse()
	{
		
		if (!isset($this->text))
		{
			return false;
		}
		
		/*
		 * Define all possible section prefixes via via regexes -- a letter, number, or series of
		 * letters that defines an individual subsection of text in a hierarchical fashion. The
		 * subsection prefix can be in one of eight formats:
		 * 
		 * 	A.
		 *	1.
		 *	a.
		 *	iv.
		 *	(A)
		 *	(1)
		 *	(a)
		 *	(iv)
		 *
		 * That, of course, is four formats expressed in two different fashions -- wrapped in
		 * parentheses or followed by a period and a space. We pair that with a list of all possible
		 * characters that can appear within that range, which we use to verify the match.
		 */
		$prefix_candidates = array	(
									'/[0-9]{1,2}\. /' => range(1, 99),
									'/\([0-9]{1,2}\) /' => range(1, 99),
									'/[a-z]{1,2}\. /' => range('a', 'z'),
									'/\([a-z]{1,2}\) /' => range('a', 'z'),
									'/[A-Z]{1,2}\. /' => range('A', 'Z'),
									'/\([A-Z]{1,2}\) /' => range('a', 'z'),
									'/([xvi]{1,4})\. /' => array('i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x', 'xi', 'xii', 'xiii', 'xiv', 'xv', 'xvi', 'xvii', 'xviii', 'xix', 'xx'),
									'/\(([xvi]{1,4})\) /' => array('i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x', 'xi', 'xii', 'xiii', 'xiv', 'xv', 'xvi', 'xvii', 'xviii', 'xix', 'xx')
									);

		/*
		 * Establish a blank prefix structure. We'll build this up and continually modify it to keep
		 * track of our current complete section number as we iterate through the text.
		 */
		$prefixes = array();
		
		/*
		 * If the text is a string, turn it into an object.
		 */
		if (is_string($this->text))
		{
			$this->text = (object) explode("\n\n", $this->text);
		}
		
		/*
		 * Deal with each subsection, one at a time.
		 */
		$i=0;
		foreach ($this->text as &$paragraph)
		{
		
			/*
			 * Set aside the first five characters in this section of text. That's the maximum number
			 * of characters that a prefix can occupy.
			 */
			$section_fragment = substr($paragraph, 0, 5);
			
			/*
			 * Iterate through our regex candidates until we find one that matches (if, indeed, one
			 * does at all).
			 */
			foreach ($prefix_candidates as $prefix => $prefix_members)
			{

				/*
				 * If this prefix isn't found in this section fragment, then proceed to the next
				 * prefix.
				 */
				preg_match($prefix, $section_fragment, $matches);
				if (count($matches) == 0)
				{
					continue;
				}

				/*
				 * If the section fragment is a Roman numeral "i", but the matched prefix candidate
				 * is alphabetic, then let's skip this prefix candidate and continue to iterate,
				 * knowing that we'll get to the Roman numeral prefix candidate soon. We ignore the
				 * last character, since that could potentially be the first character of the text
				 * (as opposed to the prefix), and is definitely not the text of our prefix (it
				 * could be a containing parenthesis, but we're not concerned about that now). We're
				 * trying to avoid actually matching an "i" if the text is, for example:
				 *
				 *	"a. in the meaning of..."
				 */
				if (strpos(substr($section_fragment, 0, -2), 'i'))
				{
					if ($prefix_members[0] == 'a')
					{
						continue;
					}
				}
				
				/*
				 * Great, we've successfully made a match -- we now know that this is the beginning
				 * of a new numbered section. First, let's save a platonic ideal of this match.
				 */
				$match = trim($matches[0]);
				
				/*
				 * Then we move this matched regex to the beginning of the $prefix_candidates stack,
				 * so that on our next iteration through we'll start with this one. We do that both
				 * in the name of efficiency and also to help Roman numerals be identified
				 * consistently, despite being comprised of letters that might reasonably be
				 * identified by another regex.
				 */
				$tmp = $prefix_candidates[$prefix];
				unset($prefix_candidates[$prefix]);
				$prefix_candidates = array_reverse($prefix_candidates);
				$prefix_candidates[$prefix] = $tmp;
				$prefix_candidates = array_reverse($prefix_candidates);
				
				/*
				 * Now we need to figure out what the entire section number is, only the very end of
				 * which is our actual prefix. To start with, we need to modify our subsection
				 * structure array to include our current prefix.
				 * 
				 * If this is our first time through, then this is easy -- our entire structure
				 * consists of the current prefix.
				 */
				if (count($prefixes) == 0)
				{
					$prefixes[] = $match;
				}
				
				/*
				 * But if we already have a prefix stored in our array of prefixes for this section,
				 * then we need to iterate through and see if there's a match.
				 */
				else
				{
					
					/*
					 * We must figure out where in the structure our current prefix lives. Iterate
					 * through the prefix structure and look for anything that matches the regex
					 * that matched our prefix.
					 */
					foreach ($prefixes as $key => &$prefix_component)
					{
					
						/*
						 * We include a space after $prefix_component because this regex is looking
						 * for a space after the prefix, something that would be there when finding
						 * this match in the context of a section, but of course we've already
						 * trimmed that out of $prefix_component.
						 */
						preg_match($prefix, $prefix_component.' ', $matches);
						if (count($matches) == 0)
						{
							continue;
						}
						
						/*
						 * We've found a match! Update our array to reflect the current section
						 * number, by modifying the relevant prefix component.
						 */	
						$prefix_component = $match;
						
						/*
						* Also, set a flag so that we know that we made a match.
						 */
						$match_made = true;
						
						/*
						 * If there are more elements in the array after this one, we need to zero
						 * them out. That is, if we're in A4(c), and our last section was A4(b)6,
						 * then we need to lop off that "6." So kill everything in the array after
						 * this.
						 */
						if (count($prefixes) > $key)
						{
							$prefixes = array_slice($prefixes, 0, ($key+1));
						}
						
					}
					
					/*
					 * If the $match_made flag hasn't been set, then we know that this is a new
					 * prefix component, and we can append it to the prefix array.
					 */
					if (!isset($match_made))
					{
						$prefixes[] = $match;
					}
					else
					{
						unset($match_made);
					}
				}		
				
				/*
				 * Iterate through the prefix structure and store each prefix section in our text
				 * object. While we're at it, eliminate any periods.
				 */
				for ($j=0; $j<count($prefixes); $j++)
				{
					$output->$i->prefix_hierarchy->$j = str_replace('.', '', $prefixes[$j]);
				}
				
				/*
				 * And store the prefix list as a single string.
				 */
				$output->$i->prefix = implode('', $prefixes);
		
			}
			
			/*
			 * Hack off the prefix at the beginning of the text and save what remains to $output.
			 */
			if (isset($output->$i->prefix))
			{
				$tmp2 = explode(' ', $paragraph);
				unset($tmp2[0]);
				$output->$i->text = implode(' ', $tmp2);
			}
			
			/*
			 * If no prefix was identified for this section, then it's a continuation of the prior
			 * section (in reality, they're probably just paragraphs, not actually "sections").
			 * Reuse the same section identifier and append the text as-is.
			 */
			if (!isset($output->$i->prefix) || empty($output->$i->prefix))
			{
				$output->$i->text = $paragraph;
				$output->$i->prefix = $output->{$i-1}->prefix;
				$output->$i->prefix_hierarchy = $output->{$i-1}->prefix_hierarchy;
			}
			
			/*
			 * We want to eliminate our matched prefix now, so that we don't mistakenly believe that
			 * we've successfully made a match on our next loop through.
			 */
			unset($match);
			
			$i++;
		}
		
		/*
		 * Store the output within the class scope, give it a better name, free up some memory, and
		 * report its success.
		 */
		$this->structured = $output;
		unset($output);
		return true;
	}
}

# Subsection Identifier

Turns theoretically structured text into actually structured text. If you have a well-structured textual document, but with structure that exists only within the text and not actually as metadata, then this is the tool to solve that problem.


# Instructions

Take the text and break it up into paragraphs, storing the text as an object, with each paragraph as a numbered property. Create a new instance of `Subsection_Identifier` (`$parser = new SubsectionIdentifier()`), store the text object as the `text` property, and then invoke the method `parse()`. That will create a member object property, `structured`, that uses the same numbered property list as the input array, with each property’s `prefix_hierarchy`, `prefix`, and `text` stored as a property.

When a structural identifer cannot be identified at the beginning of a paragraph, it will be assumed that the paragraph is continuation of the subsection identified for the prior paragraph.

This method looks for structural identifiers of the following formats:

* `A. `: One or two capital letters, followed by a period and a space.
* `1. `: One or two digits, followed by a period and a space.
* `a. `: One or two lowercase letters, followed by a period and a space.
* `(1) ` One or two digits, wrapped in parentheses, followed by a space.
* `(a) ` One or two lowercase letters, wrapped in parentheses, followed by a space.

To add other structural identifiers, simply write a PCRE to identify them and add them to the `$prefix_candidates` array. Note that only the first 5 characters of each line are searched for a structural identifier, so if you want to find longer structural identifiers, modify `$section_fragment = substr($paragraph, 0, 5);` to extract a longer substring.


# Example

For example, this text, [from the Virginia Administrative Code](http://leg1.state.va.us/cgi-bin/legp504.exe?000+reg+16VAC20-11-80):

```
A. The agency may appoint a negotiated rulemaking panel (NRP) if a regulatory action is expected to be controversial.
B. An NRP that has been appointed by the agency may be dissolved by the agency when:
1. There is no longer controversy associated with the development of the regulation;
2. The agency determines that the regulatory action is either exempt or excluded from the requirements of the Administrative Process Act; or
3. The agency determines that resolution of a controversy is unlikely.
```

It is well structured, in the sense that its structure is clear to a human looking at it, but there is no metadata providing structure that is machine readable.

To improve this text with this tool, first break it up into its component paragraphs and store it as an object (`$text = (object) explode(PHP_EOL, $text);`), which yields the following:

```
stdClass Object
(
    [0] => A. The agency may appoint a negotiated rulemaking panel (NRP) if a regulatory action is expected to be controversial.
    [1] => B. An NRP that has been appointed by the agency may be dissolved by the agency when:
    [2] => 1. There is no longer controversy associated with the development of the regulation;
    [3] => 2. The agency determines that the regulatory action is either exempt or excluded from the requirements of the Administrative Process Act; or
    [4] => 3. The agency determines that resolution of a controversy is unlikely.
)
```

Then feed that text into Subsection Identifier:

```php
$parser = new SubsectionIdentifier();
$parser->text = $text;
$parser->parse();
```

This returns the following as `$parser->structured`:

```
(
	[text] => 
	[0] => stdClass Object
		(
			[prefix_hierarchy] => stdClass Object
				(
					[0] => A
				)

			[prefix] => A.
			[text] => The agency may appoint a negotiated rulemaking panel (NRP) if a regulatory action is expected to be controversial.
		)

	[1] => stdClass Object
		(
			[prefix_hierarchy] => stdClass Object
				(
					[0] => B
				)

			[prefix] => B.
			[text] => An NRP that has been appointed by the agency may be dissolved by the agency when:
		)

	[2] => stdClass Object
		(
			[prefix_hierarchy] => stdClass Object
				(
					[0] => B
					[1] => 1
				)

			[prefix] => B.1.
			[text] => There is no longer controversy associated with the development of the regulation;
		)

	[3] => stdClass Object
		(
			[prefix_hierarchy] => stdClass Object
				(
					[0] => B
					[1] => 2
				)

			[prefix] => B.2.
			[text] => The agency determines that the regulatory action is either exempt or excluded from the requirements of the Administrative Process Act; or
		)

	[4] => stdClass Object
		(
			[prefix_hierarchy] => stdClass Object
				(
					[0] => B
					[1] => 3
				)

			[prefix] => B.3.
			[text] => The agency determines that resolution of a controversy is unlikely.
		)

)
```

# Shortcomings

This cannot determine when a child subsection terminates and the following paragraph belongs to the parent subsection. For example:

```
B. The following transactions are exempted from the securities, broker-dealer and agent registration requirements of this chapter: 

  1. Any isolated transaction by the owner or pledgee of a security, whether effected through a broker-dealer or not, which is not directly or indirectly for the benefit of the issuer; 

  2. Any nonissuer distribution by a registered broker-dealer and its registered agent of a security that has been outstanding in the hands of the public for the past five years;

  If the issuer in each of the past three fiscal years has lawfully paid dividends on its common stock aggregating at least four percent of its current market price;

except as expressly provided in this subsection.
```

The final fragment—“except as expressly provided in this subsection” properly belongs to B, but this method will assign it to B2. That's because unlabelled paragraphs are presumed to be a continuation of the prior paragraph; there is no method by which “except as expressly provided in this subsection” can be known to be a continuation of “and agent registration requirements of this chapter.”

# To Do
[Thom Neale points out a use case](https://twitter.com/twneale/status/306080682491396096) that is not allowed for, but that should be:

```
(h) Lorem ipsum dolor sit amet, consectetur adipiscing elit.
	(i) Integer tincidunt, sem eu pretium condimentum.
	(ii) Sed dui justo, euismod nec mattis a, aliquet quis ante.
(i) Nulla dapibus sem et ligula consectetur vitae sagittis arcu varius.
(j) Proin a mauris sit amet enim ullamcorper ultricies vitae id lectus.
```

This is a non-trivial modification, because it requires statefulness—an understanding, upon “realizing” that it’s in the midst of a list of Roman numerals, that it must backtrack, reevaluate where that list began, and modify the ancestry of those subsections accordingly. If it encountered only a single subsection of `(i)`, that's especially problematic, because it’s two “i”s in a row, and there’s no hint available that one of them should be a Roman numeral and, thus, a child of `(h)`. That requires an understanding of order (alphabetic, numeric, and Roman numeric) that is not currently present in this, but that seems conceptually straightforward to add.

Thom has found the example problem within the U.S. Code, so it’s not merely hypothetical.

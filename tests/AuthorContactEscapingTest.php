<?php

/**
 * Pins how a stored author contact detail becomes markup on the author template.
 *
 * The author template builds each contact row by concatenating the stored value straight
 * into an href and into the link text. No escaping function is applied on the way out, so
 * whatever is stored is what appears in the page.
 *
 * Three things are recorded here, because the change that is coming depends on all three:
 *
 *  1. What the template is handed. WordPress stores contact methods through
 *     sanitize_text_field(), which strips tags and collapses whitespace and does nothing
 *     else. Quotes and scheme prefixes come back out unchanged.
 *  2. Which escaper actually changes the result. esc_attr() encodes quotes but leaves a
 *     "javascript:" prefix alone; esc_url() drops the prefix outright. The fix relies on
 *     that difference, so it is worth writing down before the fix rather than after.
 *  3. The markup itself, built the same way the template builds it -- once as it is emitted
 *     today, and once as it will be emitted afterwards.
 *
 * The author template is a top-level file with no function boundary, so it cannot be
 * require'd from a unit test. The last two tests therefore reproduce the concatenation
 * rather than running the template.
 *
 * The two probe strings below are deliberately inert. They are here to show whether an
 * escaping function ran, not to demonstrate anything.
 */
class AuthorContactEscapingTest extends Easel_TestCase {

	/** Ends an attribute early and starts another one. */
	const QUOTE_PROBE = '" onmouseover="x';

	/** A scheme esc_url() refuses and esc_attr() does not. */
	const SCHEME_PROBE = 'javascript:x';

	/**
	 * sanitize_text_field() is what stands between the profile form and the database for a
	 * contact method. It leaves both probes alone, so the value the template reads is the
	 * value that was typed.
	 */
	public function testStoredContactValueKeepsItsQuotesAndScheme() {
		$this->assertSame( self::QUOTE_PROBE, sanitize_text_field( self::QUOTE_PROBE ) );
		$this->assertSame( self::SCHEME_PROBE, sanitize_text_field( self::SCHEME_PROBE ) );

		// It is not a no-op -- it does strip tags. It just does not touch either probe.
		$this->assertSame( 'x', sanitize_text_field( '<b>x</b>' ) );
	}

	/**
	 * esc_attr() is the wrong tool for an href: it closes the quote hole and leaves the
	 * scheme. Pinned because the fix uses esc_url() instead, and this is why.
	 */
	public function testEscAttrEncodesQuotesButKeepsTheJavascriptScheme() {
		$this->assertSame( '&quot; onmouseover=&quot;x', esc_attr( self::QUOTE_PROBE ) );
		$this->assertSame( self::SCHEME_PROBE, esc_attr( self::SCHEME_PROBE ) );
	}

	/**
	 * esc_url() closes both: the scheme is not on its allow list, and the quote does not
	 * survive its character filtering either.
	 */
	public function testEscUrlDropsTheJavascriptSchemeAndTheQuote() {
		$this->assertSame( '', esc_url( self::SCHEME_PROBE ) );
		$this->assertStringNotContainsString( '"', esc_url( self::QUOTE_PROBE ) );
	}

	/**
	 * The row the template builds today, for a contact method that is emitted raw into both
	 * the href and the link text.
	 *
	 * After the fix the row is built as
	 *     '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html( $value ) . '</a>'
	 * and the expected strings become the ones in testEscapedContactRowIsWhatTheFixEmits()
	 * below: one href attribute, and no attribute boundary anywhere in the text.
	 */
	public function testContactRowIsBuiltWithoutEscapingToday() {
		$this->assertSame(
			'<a href="" onmouseover="x" target="_blank">" onmouseover="x</a>',
			$this->contactRow( self::QUOTE_PROBE ),
			'the stored value ends the href early and adds a second attribute'
		);

		$this->assertSame(
			'<a href="javascript:x" target="_blank">javascript:x</a>',
			$this->contactRow( self::SCHEME_PROBE ),
			'the stored scheme reaches the href unchanged'
		);
	}

	/**
	 * The same two values once the row is built with esc_url() and esc_html().
	 *
	 * This test passes today as well -- it is describing the escaping functions, not the
	 * template -- and it exists so the expected post-fix output is written down next to the
	 * pre-fix output rather than only in a comment.
	 */
	public function testEscapedContactRowIsWhatTheFixEmits() {
		$this->assertSame(
			'<a href=" onmouseover=x" target="_blank">&quot; onmouseover=&quot;x</a>',
			$this->escapedContactRow( self::QUOTE_PROBE )
		);

		$this->assertSame(
			'<a href="" target="_blank">javascript:x</a>',
			$this->escapedContactRow( self::SCHEME_PROBE )
		);
	}

	/** The concatenation the template performs for a contact row, as it stands. */
	private function contactRow( $value ) {
		return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
	}

	/** The same concatenation with the escaping the fix adds. */
	private function escapedContactRow( $value ) {
		return '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html( $value ) . '</a>';
	}
}

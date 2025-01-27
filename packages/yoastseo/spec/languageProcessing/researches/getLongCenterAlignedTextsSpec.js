import getLongCenterAlignedTexts from "../../../src/languageProcessing/researches/getLongCenterAlignedTexts.js";
import Paper from "../../../src/values/Paper.js";

describe( "a test for getting elements of too long center aligned text", function() {
	it( "returns the text and type of element if a too long paragraph with center aligned text is found", function() {
		const mockPaper = new Paper( "<p class=\"has-text-align-center\">This is a paragraph with a bit more than fifty characters." +
			"</p><p class=\"has-text-align-center\">This is a short text.</p>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [
			{ text: "<p class=\"has-text-align-center\">This is a paragraph with a bit more than fifty characters.</p>", elementType: "paragraph" },
		] );
	} );
	it( "returns one object for each too long paragraph", function() {
		const mockPaper = new Paper( "<p class=\"has-text-align-center\">This is a paragraph with a bit more than fifty characters." +
			"</p><p class=\"has-text-align-center\">This is another paragraph with a bit more than fifty characters.</p>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [
			{
				text: "<p class=\"has-text-align-center\">This is a paragraph with a bit more than fifty characters.</p>",
				elementType: "paragraph",
			},
			{
				text: "<p class=\"has-text-align-center\">This is another paragraph with a bit more than fifty characters.</p>",
				elementType: "paragraph",
			},
		] );
	} );
	it( "returns the text and type of element if a too long heading with center aligned text is found", function() {
		const mockPaper = new Paper( "<h2 class=\"has-text-align-center\">This is a heading with a bit more than fifty characters." +
			"</h2><h2 class=\"has-text-align-center\">This is a short heading.</h2>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [
			{ text: "<h2 class=\"has-text-align-center\">This is a heading with a bit more than fifty characters.</h2>", elementType: "heading" },
		] );
	} );
	it( "returns one object for each too long heading", function() {
		const mockPaper = new Paper( "<h3 class=\"has-text-align-center\">This is a heading with a bit more than fifty characters." +
			"</h3><h4 class=\"has-text-align-center\">This is another heading with a bit more than fifty characters.</h4>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [
			{
				text: "<h3 class=\"has-text-align-center\">This is a heading with a bit more than fifty characters.</h3>",
				elementType: "heading",
			},
			{
				text: "<h4 class=\"has-text-align-center\">This is another heading with a bit more than fifty characters.</h4>",
				elementType: "heading",
			},
		] );
	} );
	it( "returns the objects for both headings and paragraphs when both contain too long center aligned text", function() {
		const mockPaper = new Paper( "<h5 class=\"has-text-align-center\">This is a heading with a bit more than fifty characters." +
			"</h5><p class=\"has-text-align-center\">This is a paragraph with a bit more than fifty characters.</p>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [
			{ text: "<p class=\"has-text-align-center\">This is a paragraph with a bit more than fifty characters.</p>", elementType: "paragraph" },
			{ text: "<h5 class=\"has-text-align-center\">This is a heading with a bit more than fifty characters.</h5>", elementType: "heading" },
		] );
	} );
	it( "also detects the center-aligned elements if the class name is in single quotes", function() {
		const mockPaper = new Paper( "<p class='has-text-align-center'>This is a paragraph with a bit more than fifty characters." +
			"</p><p class='has-text-align-center'>This is a short text.</p>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [
			{ text: "<p class='has-text-align-center'>This is a paragraph with a bit more than fifty characters.</p>", elementType: "paragraph" },
		] );
	} );
	it( "also detects the center-aligned elements if there are multiple class names", function() {
		const mockPaper = new Paper( "<p class='block-editor-rich-text__editable block-editor-block-list__block" +
			" wp-block has-text-align-center is-selected wp-block-heading rich-text'>This is a paragraph with a bit more than fifty characters." +
			"</p><p class='has-text-align-center'>This is a short text.</p>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [
			{ text: "<p class='block-editor-rich-text__editable block-editor-block-list__block wp-block has-text-align-center is-selected " +
					"wp-block-heading rich-text'>This is a paragraph with a bit more than fifty characters.</p>", elementType: "paragraph" },
		] );
	} );
	it( "does not include html tags in the character count", function() {
		const mockPaper = new Paper( "<p class=\"has-text-align-center\">This text is too long if you count html tags.</p>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [] );
	} );
	it( "returns an empty array if no long center aligned texts are found", function() {
		const mockPaper = new Paper( "<p>Lorem ipsum</p><h3>heading</h3>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [] );
	} );
	it( "returns an empty array if an element with short center aligned text is found", function() {
		const mockPaper = new Paper( "<p class=\"has-text-align-center\">Lorem ipsum</p><h4 class=\"has-text-align-center\">Lorem ipsum</h4>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [] );
	} );
	it( "returns an empty array if the element with center alignment is not a paragraph or a heading", function() {
		const mockPaper = new Paper( "<ul class=\"has-text-align-center\"><li>List item</li></ul>" );
		expect( getLongCenterAlignedTexts( mockPaper ) ).toEqual( [] );
	} );
} );

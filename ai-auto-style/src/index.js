( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { PanelBody, TextControl, Button, Spinner, Notice } = wp.components;
	const { Fragment, useState } = wp.element;
	const { apiFetch } = wp;
	const { createBlock } = wp.blocks;
	const { dispatch } = wp.data;

	/** Map shorthand names to actual Gutenberg/GreenShift block names */
	const BLOCK_MAP = {
		container: 'greenshift-blocks/box',
		row: 'greenshift-blocks/row',
		column: 'greenshift-blocks/column',
		heading: 'greenshift-blocks/advanced-heading',
		text: 'core/paragraph',
		paragraph: 'core/paragraph',
		image: 'greenshift-blocks/advanced-image',
		button: 'greenshift-blocks/button',
		video: 'greenshift-blocks/video',
		infobox: 'greenshift-blocks/infobox',
	};

	/**
	 * Translate AI block object to Gutenberg block.
	 * @param {Object} aiBlock { name, attributes, innerBlocks }
	 * @return {BlockInstance}
	 */
	const buildBlockFromAI = ( aiBlock ) => {
		if ( ! aiBlock || ! aiBlock.name ) {
			throw new Error( 'Invalid block definition.' );
		}

		const blockName = BLOCK_MAP[ aiBlock.name ] || aiBlock.name; // fallback to provided name
		const attrs = aiBlock.attributes || {};

		let innerBlocks = [];
		if ( Array.isArray( aiBlock.innerBlocks ) && aiBlock.innerBlocks.length ) {
			innerBlocks = aiBlock.innerBlocks.map( buildBlockFromAI );
		}

		return createBlock( blockName, attrs, innerBlocks );
	};

	const insertGeneratedBlocks = ( layout ) => {
		if ( ! layout || ! Array.isArray( layout.sections ) ) {
			console.warn( 'AI Auto Style: invalid layout schema', layout );
			throw new Error( 'Invalid layout structure.' );
		}

		let blocksToInsert = [];
		layout.sections.forEach( ( section ) => {
			if ( ! section.blocks || ! Array.isArray( section.blocks ) ) {
				return;
			}
			section.blocks.forEach( ( blk ) => {
				try {
					blocksToInsert.push( buildBlockFromAI( blk ) );
				} catch ( e ) {
					console.error( 'Block build failed', e );
				}
			} );
		} );

		if ( blocksToInsert.length ) {
			dispatch( 'core/block-editor' ).insertBlocks( blocksToInsert );
		}
	};

	const AIAutoStyleSidebar = () => {
		const [ prompt, setPrompt ] = useState( '' );
		const [ loading, setLoading ] = useState( false );
		const [ error, setError ] = useState( null );
		const [ success, setSuccess ] = useState( false );

		const handleGenerate = async () => {
			if ( ! prompt ) {
				setError( 'Prompt is required.' );
				return;
			}

			setLoading( true );
			setError( null );
			setSuccess( false );

			try {
				const result = await apiFetch( {
					path: '/ai-auto-style/v1/generate',
					method: 'POST',
					data: { prompt },
				} );

				if ( result && result.success ) {
					insertGeneratedBlocks( result.data );
					setSuccess( true );
					setError( null );
					setPrompt( '' );
				} else {
					setError( 'Unexpected response from server.' );
				}
			} catch ( err ) {
				setError( err.message || 'Request failed.' );
			} finally {
				setLoading( false );
			}
		};

		return (
			<Fragment>
				<PluginSidebarMoreMenuItem target="ai-auto-style-sidebar">
					AI Auto Style
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="ai-auto-style-sidebar"
					title="AI Auto Style"
					icon="art"
				>
					<PanelBody title="Generate Styles" initialOpen={ true }>
						<TextControl
							label="Prompt"
							value={ prompt }
							onChange={ setPrompt }
							placeholder="Describe the layout you want..."
						/>
						<Button
							isPrimary
							disabled={ loading }
							onClick={ handleGenerate }
						>
							{ loading ? <Spinner /> : 'Generate & Insert' }
						</Button>
						{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }
						{ success && ! error && <Notice status="success" isDismissible={ false }>{ 'Blocks inserted!' }</Notice> }
					</PanelBody>
				</PluginSidebar>
			</Fragment>
		);
	};

	registerPlugin( 'ai-auto-style', {
		icon: 'art',
		render: AIAutoStyleSidebar,
	} );
} )( window.wp );
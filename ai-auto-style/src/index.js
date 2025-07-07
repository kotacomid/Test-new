( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { PanelBody, TextControl, Button, Spinner, Notice } = wp.components;
	const { Fragment, useState } = wp.element;
	const { apiFetch } = wp;
	const { createBlock } = wp.blocks;
	const { dispatch } = wp.data;

	/**
	 * Helper: insert generated blocks into the editor.
	 * Expects schema with shape { sections: [ { blocks: [ { name, attributes } ] } ] }
	 * @param {Object} layout Layout JSON returned from AI.
	 */
	const insertGeneratedBlocks = ( layout ) => {
		if ( ! layout || ! Array.isArray( layout.sections ) ) {
			// eslint-disable-next-line no-console
			console.warn( 'AI Auto Style: invalid layout schema', layout );
			return;
		}

		const blocksToInsert = [];
		layout.sections.forEach( ( section ) => {
			if ( ! section.blocks || ! Array.isArray( section.blocks ) ) {
				return;
			}
			section.blocks.forEach( ( blk ) => {
				try {
					blocksToInsert.push( createBlock( blk.name, blk.attributes || {} ) );
				} catch ( e ) {
					// eslint-disable-next-line no-console
					console.error( 'Failed to create block', blk, e );
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
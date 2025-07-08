/* eslint-disable no-console */
( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { PanelBody, SelectControl, TextControl, Button, Spinner, Notice } = wp.components;
	const { Fragment, useState, useEffect } = wp.element;
	const { rawHandler } = wp.blocks;
	const { dispatch } = wp.data;

	const apiFetch = wp.apiFetch;

	const Sidebar = () => {
		const [ templates, setTemplates ] = useState( {} );
		const [ templatesLoading, setTemplatesLoading ] = useState( true );
		const [ templateSlug, setTemplateSlug ] = useState( '' );
		const [ business, setBusiness ] = useState( '' );
		const [ loading, setLoading ] = useState( false );
		const [ error, setError ] = useState( null );
		const [ success, setSuccess ] = useState( false );

		// Fetch template list on mount.
		useEffect( () => {
			(async () => {
				try {
					const data = await apiFetch( { path: '/ai-auto-style/v1/templates' } );
					setTemplates( data );
					const firstKey = Object.keys( data )[ 0 ] || '';
					setTemplateSlug( firstKey );
				} catch ( e ) {
					console.error( e );
					setError( 'Gagal memuat daftar template.' );
				} finally {
					setTemplatesLoading( false );
				}
			})();
		}, [] );

		const handleGenerate = async () => {
			setError( null );
			setSuccess( false );
			if ( ! templateSlug ) {
				setError( 'Pilih template.' );
				return;
			}
			if ( ! business ) {
				setError( 'Deskripsi bisnis wajib diisi.' );
				return;
			}

			setLoading( true );
			try {
				const resp = await apiFetch( {
					path: '/ai-auto-style/v1/fill',
					method: 'POST',
					data: { template: templateSlug, business },
				} );

				if ( resp && resp.success && resp.html ) {
					const blocks = rawHandler( { HTML: resp.html } );
					dispatch( 'core/block-editor' ).insertBlocks( blocks );
					setSuccess( true );
					setBusiness( '' );
				} else {
					setError( 'Respons server tidak valid.' );
				}
			} catch ( e ) {
				setError( e.message || 'Permintaan gagal.' );
				console.error( e );
			} finally {
				setLoading( false );
			}
		};

		return (
			<Fragment>
				<PluginSidebarMoreMenuItem target="ai-template-filler">
					AI Template Filler
				</PluginSidebarMoreMenuItem>
				<PluginSidebar name="ai-template-filler" title="AI Template Filler" icon="welcome-add-page">
					<PanelBody title="Generate Landing Page" initialOpen>
						{ templatesLoading && <Spinner /> }
						{ ! templatesLoading && (
							<Fragment>
								<SelectControl
									label="Pilih Template"
									value={ templateSlug }
									options={ Object.entries( templates ).map( ( [ key, obj ] ) => ( { label: obj.title, value: key } ) ) }
									onChange={ setTemplateSlug }
								/>
								<TextControl
									label="Deskripsi Bisnis"
									value={ business }
									onChange={ setBusiness }
									placeholder="Contoh: Laundry kiloan berbasis aplikasi mobile"
								/>
								<Button isPrimary disabled={ loading } onClick={ handleGenerate }>
									{ loading ? <Spinner /> : 'Generate & Insert' }
								</Button>
							</Fragment>
						) }
						{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }
						{ success && <Notice status="success" isDismissible={ false }>Blok berhasil ditambahkan!</Notice> }
					</PanelBody>
				</PluginSidebar>
			</Fragment>
		);
	};

	registerPlugin( 'ai-template-filler', {
		icon: 'welcome-add-page',
		render: Sidebar,
	} );
} )( window.wp );
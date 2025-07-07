( function ( wp ) {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { PanelBody, TextControl, Button } = wp.components;
    const { Fragment, useState } = wp.element;

    const AIAutoStyleSidebar = () => {
        const [ prompt, setPrompt ] = useState( '' );

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
                        />
                        <Button
                            isPrimary
                            onClick={ () => {
                                // TODO: integrate REST call to generate styles.
                                console.log( 'Generate for prompt:', prompt );
                            } }
                        >
                            Generate
                        </Button>
                    </PanelBody>
                </PluginSidebar>
            </Fragment>
        );
    };

    registerPlugin( 'ai-auto-style', {
        render: AIAutoStyleSidebar,
        icon: 'art',
    } );
} )( window.wp );
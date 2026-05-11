import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import metadata from '../block.json';

registerBlockType( metadata.name, {
    edit: function Edit( { attributes, setAttributes } ) {
        const { title, subtitle } = attributes;
        const blockProps = useBlockProps();

        return (
            <>
                {/* Painel lateral de configurações */}
                <InspectorControls>
                    <PanelBody title="Configurações do formulário">
                        <TextControl
                            label="Título"
                            value={ title }
                            onChange={ ( val ) => setAttributes({ title: val }) }
                        />
                        <TextControl
                            label="Subtítulo"
                            value={ subtitle }
                            onChange={ ( val ) => setAttributes({ subtitle: val }) }
                        />
                    </PanelBody>
                </InspectorControls>

                {/* Preview no editor */}
                <div { ...blockProps }>
                    <div style={{
                        border: '1px dashed #AFA9EC',
                        borderRadius: 8,
                        padding: 20,
                        textAlign: 'center',
                        background: '#EEEDFE'
                    }}>
                        <p style={{ margin: 0, fontWeight: 500, color: '#3C3489' }}>
                            WP Booking — Formulário de agendamento
                        </p>
                        <p style={{ margin: '4px 0 0', fontSize: 13, color: '#534AB7' }}>
                            { title }
                        </p>
                    </div>
                </div>
            </>
        );
    },

    // save: null = renderizado pelo PHP (render.php)
    save: () => null,
});
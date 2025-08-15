import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { plus, closeSmall } from '@wordpress/icons';
import './editor.scss';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { question, options, blockId, allowAnonymous } = attributes;
	// const [ allowAnonymous, setAllowAnonymous ] = useState( false );

	// Set a unique blockId if not already set.
	useEffect( () => {
		if ( ! blockId ) {
			setAttributes( { blockId: clientId } );
		}
	}, [ blockId, clientId, setAttributes ] );

	// Add a new option.
	const addOption = () => {
		setAttributes( { options: [ ...options, { option: '', votes: 0 } ] } );
	};

	// Remove an option.
	const removeOption = ( index ) => {
		const newOptions = [ ...options ];
		newOptions.splice( index, 1 );
		setAttributes( { options: newOptions } );
	};

	// Update option text.
	const updateOptionText = ( index, value ) => {
		const updatedOptions = options.map( ( option, i ) =>
			i === index ? { ...option, option: value } : option
		);
		setAttributes( { options: updatedOptions } );
	};

	return (
		<div { ...useBlockProps() } key={ blockId }>
			<InspectorControls>
				<PanelBody title={ __( 'Poll Setting', 'toggle-content' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ allowAnonymous }
						label={ __( 'Allow Anonymous Polls', 'polls-block' ) }
						onChange={ ( val ) => {
							setAttributes( { allowAnonymous: val } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<div className="poll-block-editor">
				<div className="poll-question">
					<RichText
						tagName="h3"
						placeholder={ __( 'Ask a question', 'polls-block' ) }
						value={ question }
						onChange={ ( value ) =>
							setAttributes( { question: value } )
						}
					/>
				</div>
				{ options.map( ( option, index ) => (
					<div key={ index } className="poll-option">
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							placeholder={
								__( 'Choice', 'polls-block' ) +
								' ' +
								( index + 1 )
							}
							value={ option.option }
							onChange={ ( value ) =>
								updateOptionText( index, value )
							}
						/>
						<Button
							icon={ closeSmall }
							isSmall
							label={ __( 'Remove option', 'polls-block' ) }
							onClick={ () => removeOption( index ) }
							className="poll-remove-button"
							disabled={ options.length <= 2 }
						></Button>
						{ index === options.length - 1 && (
							<Button
								icon={ plus }
								onClick={ addOption }
								className="poll-add-button"
								label={ __( 'Add option', 'polls-block' ) }
							/>
						) }
					</div>
				) ) }
			</div>
		</div>
	);
}

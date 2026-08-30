import React from 'react';
import Icon from './Icon';

/**
 * File type icon with appropriate colors and material glyphs.
 */
export const FileIcon = ( { type, isFolder, mimeType, size = 24 } ) => {
	if ( isFolder || type === 'folder' ) {
		return (
			<Icon
				name="folder"
				size={ size }
				color="#fbbc04"
				className="drivevault-folder-icon"
			/>
		);
	}

	switch ( type ) {
		case 'pdf':
			return <Icon name="picture_as_pdf" size={ size } color="#ea4335" />;
		case 'image':
			return <Icon name="image" size={ size } color="#34a853" />;
		case 'video':
			return <Icon name="movie" size={ size } color="#ea4335" />;
		case 'audio':
			return <Icon name="music_note" size={ size } color="#fbbc04" />;
		case 'archive':
			return <Icon name="folder_zip" size={ size } color="#4285f4" />;
		case 'spreadsheet':
			return <Icon name="table_chart" size={ size } color="#0f9d58" />;
		case 'document':
			return <Icon name="description" size={ size } color="#4285f4" />;
		default:
			return (
				<Icon name="insert_drive_file" size={ size } color="#5f6368" />
			);
	}
};

export default FileIcon;

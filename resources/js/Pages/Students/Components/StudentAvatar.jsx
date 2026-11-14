import React from 'react';
import Avatar from '@/Components/UI/Avatar';

export default function StudentAvatar({ name, photoUrl, size = 'w-9 h-9', textSize = 'text-xs' }) {
    if (photoUrl) {
        return (
            <img
                src={photoUrl}
                alt={name}
                className={`${size} rounded-full object-cover bg-surface-container-low`}
            />
        );
    }

    return <Avatar label={name ?? '?'} size={size} textSize={textSize} />;
}

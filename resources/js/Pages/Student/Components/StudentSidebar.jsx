import React from 'react';
import DeviceReadiness from './DeviceReadiness';

export default function StudentSidebar({ children }) {
    return (
        <aside className="flex flex-col gap-5 lg:col-span-4">
            <DeviceReadiness />
            {children}
        </aside>
    );
}

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    isManager: boolean | undefined;
    user: User;
    isAdmin?: boolean;
    canViewPendingClients?: boolean;
    canViewMyApprovedClients?: boolean;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

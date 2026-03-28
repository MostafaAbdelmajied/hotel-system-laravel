export type Floor = {
    id: number;
    name: string;
    number: string;
    created_by?: number;
    creator?: {
        id: number;
        name: string;
    } | null;
};

declare global {
  interface Window {
    page?: {
      props?: {
        locale?: string;
        dir?: string;
        auth?: {
          user?: any;
          permissions?: string[];
          roles?: string[];
        };
      };
    };
  }
}

export {};
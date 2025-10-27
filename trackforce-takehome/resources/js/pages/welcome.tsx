import { Head } from '@inertiajs/react';

export default function Welcome() {

    return (
        <>
            <Head title="Welcome">
            <title>Charles Unger-Peters TrackTik Take home</title>
            <meta name="description" content="Charles Unger-Peters TrackTik take-home assignment. " />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#ff0000] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                    <nav className="flex items-center justify-end gap-4">
                        </nav>
                   </header>
                   <main className="flex flex-col items-center justify-center">
                    <div className="max-w-screen-xl mx-auto">
                    <h1>Charles Unger-Peters TrackTik Take home</h1>
                    </div>
                   </main>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}

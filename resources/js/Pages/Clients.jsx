import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head ,useForm} from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';


export default function Clients({ auth, clients }) { 
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        redirect: ''
    });
    console.log(clients);
    const submit = (e) => {
        e.preventDefault();
        post('/oauth/clients');
    };
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Client</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">Here are a list of your clients: 
                        {clients.map((e)=>{
                            return(
                                <div className=" py-3 text-gray-900">
                                    <h3 className="text-lg text-gray-500"><b>Client ID: </b>{e.id}</h3>
                                    <h3 className="text-lg text-gray-500"><b>Client Name: </b>{e.name}</h3>
                                    <h3 className="text-lg text-gray-500"><b>Client Secret: </b>{e.secret}</h3>
                                    <p><b>Client Redirect: </b>{e.redirect}</p>
                                </div>
                            );
                        })}
                        </div>
                        <div className="p-6 text-gray-900">
                        <form onSubmit={submit}>
                            <div>
                                <InputLabel htmlFor="name" value="Name" />
                                <TextInput
                                    id="name"
                                    type="name"
                                    name="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    autoComplete=""
                                    isFocused={false}
                                    onChange={(e) => setData('name', e.target.value)}
                                />

                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div className="mt-4">
                                <InputLabel htmlFor="redirect" value="Redirect" />

                                <TextInput
                                    id="redirect"
                                    type="redirect"
                                    name="redirect"
                                    value={data.redirect}
                                    className="mt-1 block w-full"
                                    autoComplete="redirect"
                                    onChange={(e) => setData('redirect', e.target.value)}
                                />

                                <InputError message={errors.redirect} className="mt-2" />
                            </div>
                            <PrimaryButton className="ms-4" disabled={processing}>
                                Create Client
                            </PrimaryButton>
                        </form>                            
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
